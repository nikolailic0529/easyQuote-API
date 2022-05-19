<?php

namespace App\Services\WorldwideQuote;

use App\Contracts\Services\ProcessesWorldwideQuoteAssetState;
use App\DTO\WorldwideQuote\{AssetServiceLevel,
    AssetServiceLookupData,
    AssetServiceLookupDataCollection,
    AssetServiceLookupResult,
    BatchAssetFileMapping,
    ImportBatchAssetFileData,
    InitializeWorldwideQuoteAssetData,
    ReadAssetRow,
    ReadBatchFileResult,
    WorldwideQuoteAssetDataCollection};
use App\Enum\Lock;
use App\Models\Address;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Services\Exceptions\ValidationException;
use App\Services\ExchangeRate\CurrencyConverter;
use App\Support\PriceParser;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class WorldwideQuoteAssetStateProcessor implements ProcessesWorldwideQuoteAssetState
{

    private array $vendorsCache = [];

    private array $machineAddressCache = [];

    public function __construct(protected ConnectionInterface       $connection,
                                protected LockProvider              $lockProvider,
                                protected ValidatorInterface        $validator,
                                protected Cache                     $cache,
                                protected Filesystem                $storage,
                                protected AssetServiceLookupService $lookupService,
                                protected CurrencyConverter         $currencyConverter)
    {
    }

    public function initializeQuoteAsset(WorldwideQuoteVersion $quote, InitializeWorldwideQuoteAssetData $data): WorldwideQuoteAsset
    {
        return tap(new WorldwideQuoteAsset(), function (WorldwideQuoteAsset $asset) use ($data, $quote) {
            $asset->worldwideQuote()->associate($quote);
            $asset->is_selected = true;

            $asset->vendor()->associate($data->vendor_id);
            $asset->buyCurrency()->associate($data->buy_currency_id);
            $asset->machineAddress()->associate($data->machine_address_id);

            $asset->country = $data->country_code;
            $asset->serial_no = $data->serial_no;
            $asset->sku = $data->sku;
            $asset->service_sku = $data->service_sku;
            $asset->product_name = $data->product_name;
            $asset->expiry_date = transform($data->expiry_date, fn(\DateTimeInterface $dateTime) => $dateTime->format('Y-m-d'));
            $asset->service_level_description = $data->service_level_description;
            $asset->buy_price = $data->buy_price;
            $asset->buy_price_margin = $data->buy_price_margin;
            $asset->price = $data->price;
            $asset->original_price = $data->original_price;
            $asset->exchange_rate_value = $data->exchange_rate_value;
            $asset->exchange_rate_margin = $data->exchange_rate_margin;
            $asset->is_warranty_checked = $data->is_warranty_checked;

            $asset->makeHidden('worldwideQuote');

            $this->connection->transaction(fn() => $asset->save());
        });
    }

    public function batchUpdateQuoteAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetDataCollection $collection)
    {
        $assetKeys = [];

        foreach ($collection as $assetData) {
            $violations = $this->validator->validate($assetData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }

            $assetKeys[] = $assetData->id;
        }

        /**
         * @var WorldwideQuoteAsset[] $assetModels
         */

        $assetModels = value(function () use ($assetKeys, $quote) {
            if ($quote->wasRecentlyCreated) {
                return WorldwideQuoteAsset::query()->whereHas('replicatedAsset', function (Builder $builder) use ($assetKeys) {
                    $builder->whereKey($assetKeys);
                })
                    ->get()
                    ->keyBy('replicated_asset_id');
            }

            return WorldwideQuoteAsset::query()->whereKey($assetKeys)->get()->keyBy('id');
        });

        $collection->rewind();

        foreach ($collection as $assetData) {
            $asset = $assetModels[$assetData->id];
            with($asset, function (WorldwideQuoteAsset $asset) use ($assetData) {

                $asset->buyCurrency()->associate($assetData->buy_currency_id);
                $asset->vendor()->associate($assetData->vendor_id);
                $asset->machineAddress()->associate($assetData->machine_address_id);
                $asset->country = $assetData->country_code;
                $asset->serial_no = $assetData->serial_no;
                $asset->sku = $assetData->sku;
                $asset->service_sku = $assetData->service_sku;
                $asset->product_name = $assetData->product_name;
                $asset->expiry_date = transform($assetData->expiry_date, fn(Carbon $date) => $date->toDateString());
                $asset->service_level_description = $assetData->service_level_description;
                $asset->buy_price = $assetData->buy_price;
                $asset->buy_price_margin = $assetData->buy_price_margin;
                $asset->price = $assetData->price;
                $asset->original_price = $assetData->original_price;
                $asset->exchange_rate_margin = $assetData->exchange_rate_margin;
                $asset->exchange_rate_value = $assetData->exchange_rate_value;
                $asset->is_warranty_checked = $assetData->is_warranty_checked;
                $asset->is_serial_number_generated = $assetData->is_serial_number_generated;

                $lock = $this->lockProvider->lock(Lock::UPDATE_WWASSET($asset->getKey()), 10);

                $lock->block(30, function () use ($asset) {

                    $this->connection->transaction(fn() => $asset->save());

                });
            });
        }
    }

    public function recalculateExchangeRateOfQuoteAssets(WorldwideQuoteVersion $quote): void
    {
        if (false === $quote->wasChanged($quote->quoteCurrency()->getForeignKeyName())) {
            return;
        }

        $quote->assets->each(function (WorldwideQuoteAsset $asset) use ($quote): void {

            if (is_null($asset->buyCurrency)) {
                return;
            }

            $exchangeRate = $this->currencyConverter->convertCurrencies(
                fromCode: $asset->buyCurrency->code,
                toCode: $quote->quoteCurrency->code,
                amount: 1,
                dateTime: $quote?->created_at
            );

            $asset->exchange_rate_value = $exchangeRate + ($exchangeRate * ($asset->exchange_rate_margin / 100));

            $asset->price = ($asset->buy_price + ($asset->buy_price * ($asset->buy_price_margin / 100))) * $asset->exchange_rate_value;

            $this->lockProvider->lock(Lock::UPDATE_WWASSET($asset->getKey()), 10)
                ->block(30, function () use ($asset) {

                    $this->connection->transaction(fn() => $asset->save());

                });

        });

    }

    public function readBatchAssetFile(UploadedFile $file): ReadBatchFileResult
    {
        $reader = new BatchAssetFileReader(
            $file->getRealPath(),
            $file->getClientOriginalExtension()
        );

        $headers = $reader->getHeaders();

        $rowsCount = 3;
        $rows = [];

        $rowsIterator = $reader->getRows();

        while (count($rows) < $rowsCount && $rowsIterator->valid()) {
            $rows[] = $rowsIterator->current();

            $rowsIterator->next();
        }

        $readRows = [];

        foreach ($headers as $key => $header) {
            $readRows[] = new ReadAssetRow([
                'header' => (string)$header,
                'header_key' => (string)$key,
                'value' => implode(', ', array_filter(Arr::pluck($rows, $key) ?? [])),
            ]);
        }

        $fileUuid = (string)Uuid::generate(4);
        $fileName = $fileUuid.'.'.$file->getClientOriginalExtension();

        $this->storage->put(
            $fileName,
            $file->get()
        );

        $this->cache->put(
            $fileUuid,
            $fileName,
            now()->addHour()
        );

        return new ReadBatchFileResult([
            'file_id' => $fileUuid,
            'read_rows' => $readRows,
        ]);
    }

    private function mapAssetDataUsingFileMapping(BatchAssetFileMapping $mapping, array $row, WorldwideQuoteVersion $quote): WorldwideQuoteAsset
    {
        return tap(new WorldwideQuoteAsset(), function (WorldwideQuoteAsset $asset) use ($row, $mapping, $quote) {
            $asset->is_selected = true;

            $asset->{$asset->getKeyName()} = (string)Uuid::generate(4);

            $asset->worldwideQuote()->associate($quote);

            $asset->buyCurrency()->associate($quote->quote_currency_id);

            $asset->serial_no = transform($mapping->serial_no, function (string $serialNoColumn) use ($row) {
                return $row[$serialNoColumn] ?? null;
            });

            $asset->sku = transform($mapping->sku, function (string $skuColumn) use ($row) {
                return $row[$skuColumn] ?? null;
            });

            $asset->service_sku = transform($mapping->service_sku, function (string $serviceSkuColumn) use ($row) {
                return $row[$serviceSkuColumn] ?? null;
            });

            $asset->product_name = transform($mapping->product_name, function (string $productNameColumn) use ($row) {
                return $row[$productNameColumn] ?? null;
            });

            $asset->expiry_date = transform($mapping->expiry_date, function (string $expiryDateColumn) use ($row) {
                $value = $row[$expiryDateColumn] ?? null;

                if (!is_null($value)) {
                    try {
                        return Carbon::parse($value)->toDateString();
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                return null;
            });

            $asset->original_price = transform($mapping->price, function (string $priceColumn) use ($row) {
                $value = $row[$priceColumn] ?? null;

                if (!is_null($value)) {
                    return PriceParser::parseAmount($value);
                }

                return null;
            });

            $asset->price = transform($mapping->price, function (string $priceColumn) use ($row) {
                $value = $row[$priceColumn] ?? null;

                if (!is_null($value)) {
                    return PriceParser::parseAmount($value);
                }

                return null;
            });

            $asset->buy_price = transform($mapping->buy_price_value, function (string $column) use ($row): ?float {
                if (isset($row[$column])) {
                    return PriceParser::parseAmount($row[$column]);
                }

                return null;
            });

            $asset->buyCurrency()->associate(transform($mapping->buy_price_currency, function (string $column) use ($row): ?Currency {
                if (isset($row[$column])) {
                    /** @noinspection PhpIncompatibleReturnTypeInspection */
                    return Currency::query()->where('code', $row[$column])->first();
                }

                return null;
            }));

            $asset->buy_price_margin = transform($mapping->buy_price_margin, function (string $column) use ($row): ?float {
                if (isset($row[$column])) {
                    return (float)$row[$column];
                }

                return null;
            });

            $asset->exchange_rate_value = transform($mapping->exchange_rate_value, function (string $column) use ($row): ?float {
                if (isset($row[$column])) {
                    return (float)$row[$column];
                }

                return null;
            });

            $asset->exchange_rate_margin = transform($mapping->exchange_rate_margin, function (string $column) use ($row): ?float {
                if (isset($row[$column])) {
                    return (float)$row[$column];
                }

                return null;
            });

            $asset->service_level_description = transform($mapping->service_level_description, function (string $serviceLevelColumn) use ($row) {
                return $row[$serviceLevelColumn] ?? null;
            });

            $asset->vendor_id = transform($mapping->vendor, function (string $vendorColumn) use ($row) {
                $value = $row[$vendorColumn] ?? null;

                if (!is_null($value)) {
                    return $this->vendorsCache[$value] ??= Vendor::query()
                        ->where('short_code', $value)
                        ->orWhere('name', $value)
                        ->value('id');
                }

                return null;
            });

            $asset->country = $country = transform($mapping->country, function (string $countryColumn) use ($row) {
                return $row[$countryColumn] ?? null;
            });

            $asset->machine_address_id = transform($mapping->street_address, function (string $streetAddressColumn) use ($row, $mapping, $country) {
                $address1 = $row[$streetAddressColumn] ?? null;

                if (is_null($address1) || trim($address1) === '') {
                    return null;
                }

                $postCode = transform($mapping->post_code, function (string $postCodeColumn) use ($row) {
                    return $row[$postCodeColumn] ?? null;
                });
                $city = transform($mapping->city, function (string $cityColumn) use ($row) {
                    return $row[$cityColumn] ?? null;
                });
                $state = transform($mapping->state, function (string $stateColumn) use ($row) {
                    return $row[$stateColumn] ?? null;
                });
                $stateCode = transform($mapping->state_code, function (string $stateCodeColumn) use ($row) {
                    return $row[$stateCodeColumn] ?? null;
                });

                $addressHash = md5($address1.$postCode.$city.$state.$stateCode);

                return $this->machineAddressCache[$addressHash] ??= with(new Address(), function (Address $address) use ($country, $stateCode, $state, $city, $postCode, $address1): string {
                    $address->address_type = 'Machine';
                    $address->address_1 = $address1;
                    $address->post_code = $postCode;
                    $address->city = $city;
                    $address->state = $state;
                    $address->state_code = $stateCode;
                    $address->country_id = Country::query()->where('iso_3166_2', $country)->value('id');

                    $address->save();

                    return $address->getKey();
                });
            });

            $asset->service_level_data = [];

            $asset->setCreatedAt($asset->freshTimestamp());
            $asset->setUpdatedAt($asset->freshTimestamp());
        });
    }

    public function importBatchAssetFile(WorldwideQuoteVersion $quote, ImportBatchAssetFileData $data)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        $fileName = $this->cache->get($data->file_id);

        if (!$this->storage->exists($fileName)) {
            throw new ValidationException('Uploaded file not found.');
        }

        $file = new \SplFileObject($this->storage->path($fileName));

        $reader = (new BatchAssetFileReader(
            $file->getRealPath(),
            $file->getExtension()
        ))
            ->fileContainsHeaders($data->file_contains_headers);

        $chunkIterator = $reader->getChunks(1000);

        $machineAddressKeys = [];

        $currencyCode = Currency::query()
            ->whereKey($quote->quote_currency_id)
            ->value('code');

        foreach ($chunkIterator as $chunk) {

            $assetBatch = array_map(
                fn(array $row) => $this->mapAssetDataUsingFileMapping($data->file_mapping, $row, $quote),
                $chunk
            );

            $assetLookupDataCollection = $this->assetBatchToLookupCollection($assetBatch, $currencyCode);

            $lookupResult = $this->lookupService->performBatchWarrantyLookup(
                $assetLookupDataCollection
            );

            $this->lookupService->guessServiceLevelDataOfAssetLookupResults($lookupResult);

            $assetBatch = $this->mapBatchLookupResultWithAssetBatch($lookupResult, $assetBatch);

            $assetBatch = array_map(fn(WorldwideQuoteAsset $asset) => $asset->getAttributes(), $assetBatch);

            $this->connection->transaction(fn() => WorldwideQuoteAsset::query()->insert($assetBatch));

            $machineAddressKeys = array_merge(
                $machineAddressKeys,
                array_values(array_filter(array_column($assetBatch, 'machine_address_id')))
            );
        }

        // Finally, we are attaching the newly created machine addresses to the opportunity.
        $machineAddressKeys = array_values(array_unique($machineAddressKeys));

        if (!empty($machineAddressKeys)) {
            $opportunityLock = $this->lockProvider->lock(Lock::UPDATE_OPPORTUNITY($quote->worldwideQuote->opportunity->getKey()), 10);

            $opportunityLock->block(30, function () use ($machineAddressKeys, $quote) {

                $this->connection->transaction(fn() => $quote->addresses()->syncWithoutDetaching($machineAddressKeys));

            });
        }
    }

    /**
     * @param AssetServiceLookupResult[] $assetServiceLookupResults
     * @param WorldwideQuoteAsset[] $assetBatch
     * @return WorldwideQuoteAsset[]
     */
    protected function mapBatchLookupResultWithAssetBatch(array $assetServiceLookupResults, array $assetBatch): array
    {
        foreach ($assetBatch as $asset) {
            $assetLookupResult = $assetServiceLookupResults[$asset->getKey()] ?? null;

            if (is_null($assetLookupResult)) {

                if (!is_null($asset->service_level_description) && !is_null($asset->price)) {
                    $asset->service_level_data = [
                        ['description' => $asset->service_level_description, 'price' => $asset->price, 'code' => $asset->service_sku],
                    ];
                }

                continue;
            }

            $assetLookupResult = $assetServiceLookupResults[$asset->getKey()];

            $asset->expiry_date = $assetLookupResult->expiry_date ?? $asset->expiry_date;

            $serviceLevelsArray = array_map(fn(AssetServiceLevel $level) => $level->toArray(), $assetLookupResult->service_levels);

            $asset->product_name = $assetLookupResult->product_name ?? $asset->product_name;

            $asset->service_sku = $assetLookupResult->service_sku ?? $asset->service_sku;

            if (!empty($serviceLevelsArray)) {
                $asset->service_level_data = $serviceLevelsArray;
            } elseif (!is_null($asset->service_level_description) && isset($asset->price)) {
                $asset->service_level_data = [
                    ['description' => $asset->service_level_description, 'price' => $asset->price, 'code' => $asset->service_sku],
                ];
            }

            if (
                !is_null($asset->service_level_description) &&
                trim($asset->service_level_description) !== '' &&
                !is_null($asset->price)
            ) {

                // When service level description is present on the asset entity,
                // we will try to find the matching service level from lookup result,
                // and set the corresponding price, service_sku to the asset entity.
                with($assetLookupResult->service_levels, function (array $serviceLevels) use ($asset) {

                    foreach ($serviceLevels as $serviceLevel) {

                        /** @var AssetServiceLevel $serviceLevel */
                        if ($serviceLevel->description === $asset->service_level_description) {
                            $asset->price = $asset->original_price = $serviceLevel->price;
                            $asset->service_sku = $serviceLevel->code;
                        }
                    }

                });
            }
        }

        return $assetBatch;
    }

    /**
     * @param WorldwideQuoteAsset[] $assetBatch
     * @param string|null $currencyCode
     * @return AssetServiceLookupDataCollection
     */
    protected function assetBatchToLookupCollection(array $assetBatch, ?string $currencyCode = null): AssetServiceLookupDataCollection
    {
        $lookupDataCollection = [];

        $vendorShortCodeResolver = function (string $vendorKey): ?string {
            static $vendorsCache = [];

            return $vendorsCache[$vendorKey] ??= Vendor::query()
                ->whereKey($vendorKey)
                ->value('short_code');
        };

        foreach ($assetBatch as $asset) {
            if (
                is_null($asset->sku) ||
                is_null($asset->serial_no) ||
                is_null($asset->vendor_id) ||
                is_null($asset->country) ||
                strlen($asset->country) !== 2
            ) {
                continue;
            }

            $vendorShortCode = $vendorShortCodeResolver($asset->vendor_id);

            // Skip an asset when vendor is not supported to perform a lookup.
            if (!in_array($vendorShortCode, ['HPE', 'LEN'], true)) {
                continue;
            }

            $lookupDataCollection[] = new AssetServiceLookupData([
                'asset_id' => $asset['id'],
                'vendor_short_code' => $vendorShortCode,
                'serial_no' => $asset['serial_no'],
                'sku' => $asset['sku'],
                'country_code' => $asset['country'],
                'currency_code' => $currencyCode,
            ]);
        }

        return new AssetServiceLookupDataCollection($lookupDataCollection);
    }

    public function deleteQuoteAsset(WorldwideQuoteVersion $quote, WorldwideQuoteAsset $asset): void
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        if ($newVersionResolved) {
            $asset = $quote->assets()->whereHas('replicatedAsset', function (Builder $builder) use ($asset) {
                $builder->whereKey($asset->getKey());
            })->sole();
        }

        $lock = $this->lockProvider->lock(Lock::DELETE_WWASSET($asset->getKey()), 10);

        $lock->block(30, function () use ($asset) {

            $this->connection->transaction(fn() => $asset->delete());

        });
    }
}
