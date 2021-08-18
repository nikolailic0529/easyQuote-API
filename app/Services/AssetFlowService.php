<?php

namespace App\Services;

use App\Contracts\{Services\ManagesExchangeRates,
    Services\MigratesAssetEntity,
    Services\MigratesCustomerEntity,
    LoggerAware};
use App\DTO\QuoteAsset;
use App\Enum\AddressType;
use App\Models\{Address,
    Asset,
    AssetCategory,
    Quote\Quote,
    Quote\WorldwideDistribution,
    Quote\WorldwideQuoteVersion,
    QuoteFile\MappedRow,
    WorldwideQuoteAsset};
use App\Models\Quote\BaseQuote;
use App\Queries\QuoteQueries;
use App\Services\Concerns\WithProgress;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Spatie\Activitylog\ActivityLogStatus;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AssetFlowService implements MigratesAssetEntity, LoggerAware
{
    use WithProgress;

    protected array $assetCategoryCache = [];

    #[Pure]
    public function __construct(protected ConnectionInterface $connection,
                                protected LoggerInterface $logger,
                                protected ValidatorInterface $validator,
                                protected QuoteQueries $quoteQueries,
                                protected ManagesExchangeRates $exchangeRateService,
                                protected MigratesCustomerEntity $customerFlowService,
                                protected ActivityLogStatus $activityLogStatus)
    {
    }

    public function migrateAssets(int $flags = 0): void
    {
        if ($flags & self::FRESH_MIGRATE) {

            $this->logger->info('FRESH_MIGRATE flag is set. Flushing migrated asset entities...');

            $deletedCount = Asset::query()
                ->where('is_migrated', true)
                ->forceDelete();

            $this->logger->info("Migrated asset entities are deleted. Deleted count: $deletedCount.");

        }

        $this->logger->info("Migrating Rescue Quote assets.");

        $migratingAssetsFromQuotes = Quote::query()
            ->where('completeness', '>', 40)
            ->lazyById(100)
            ->filter(function (Quote $quote) use ($flags) {
                if ($flags & self::FRESH_MIGRATE) {
                    return true;
                }

                return is_null($quote->assets_migrated_at);
            });

        foreach ($migratingAssetsFromQuotes as $quote) {

            $this->migrateAssetsFromRescueQuote($quote);

        }

        $this->logger->info("Rescue Quote assets have been migrated.");

        $this->logger->info("Migrating Worldwide Quote assets.");

        $migratingAssetsFromWorldwideQuotes = WorldwideQuoteVersion::query()
            ->where('completeness', '>', 40)
            ->lazyById(100)
            ->filter(function (WorldwideQuoteVersion $quoteVersion) use ($flags) {

                if ($flags & self::FRESH_MIGRATE) {
                    return true;
                }

                return is_null($quoteVersion->assets_migrated_at);

            });


        foreach ($migratingAssetsFromWorldwideQuotes as $worldwideQuote) {

            $this->migrateAssetsFromWorldwideQuote($worldwideQuote);

        }

        $this->logger->info("Worldwide Quote assets have been migrated.");

    }

    public function migrateAssetsFromWorldwideQuote(WorldwideQuoteVersion $quote): void
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        match ($quote->worldwideQuote->contract_type_id) {
            CT_CONTRACT => $this->migrateAssetsFromWorldwideContractQuote($quote),
            CT_PACK => $this->migrateAssetsFromWorldwidePackQuote($quote),
        };

        with($quote, function (WorldwideQuoteVersion $version) {

            $version->assets_migrated_at = now();

            $this->connection->transaction(fn() => $version->save());

        });

        $this->logger->info(ASSET_MGQF_01, [
            'quote_id' => $quote->getKey(),
            'quote_number' => $quote->worldwideQuote->quote_number,
            'entity_class' => $quote::class,
        ]);
    }

    protected function migrateAssetsFromWorldwideContractQuote(WorldwideQuoteVersion $quote): void
    {
        foreach ($quote->worldwideDistributions as $distributorQuote) {

            if ($distributorQuote->vendors()->doesntExist()) {
                continue;
            }

            foreach ($distributorQuote->mappedRows as $mappedRow) {

                $this->performMigrationOfGenericQuoteAssetData(
                    $this->buildWorldwideContractQuoteAssetData($mappedRow, $distributorQuote)
                );

            }

        }
    }

    protected function buildWorldwideContractQuoteAssetData(MappedRow $mappedRow, WorldwideDistribution $distributorQuote): QuoteAsset
    {
        $assetCategory = match (true) {
            (float)$mappedRow->original_price === 0.0 => $this->getAssetCategory('Software'),
            default => $this->getAssetCategory('Hardware'),
        };

        /** @var Address|null $assetAddress */
        $assetAddress = match ($assetCategory->name) {
            'Software' => $distributorQuote->addresses()->where('address_type', AddressType::SOFTWARE)->orderByDesc('is_default')->first(),
            'Hardware' => $distributorQuote->addresses()->where('address_type', AddressType::HARDWARE)->orderByDesc('is_default')->first(),
        };

        $baseRate = value(function () use ($distributorQuote): float {

            if (is_null($distributorQuote->distributionCurrency)) {
                return 1.0;
            }

            return $this->exchangeRateService->getBaseRate(
                $distributorQuote->distributionCurrency,
                $distributorQuote->{$distributorQuote->getCreatedAtColumn()}
            );

        });

        $dateFrom = transform($mappedRow->date_from, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));
        $dateTo = transform($mappedRow->date_to, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        return new QuoteAsset([
            'user_id' => $distributorQuote->worldwideQuote->user_id,
            'address_id' => $assetAddress?->getKey(),
            'vendor_id' => $distributorQuote->vendors[0]?->getKey(),
            'quote_id' => $distributorQuote->worldwideQuote->worldwideQuote->getKey(),
            'quote_type' => $distributorQuote->worldwideQuote->worldwideQuote->getMorphClass(),
            'company_reference_id' => $distributorQuote->worldwideQuote->worldwideQuote->opportunity->primaryAccount?->getKey(),
            'vendor_short_code' => $distributorQuote->vendors[0]?->short_code,
            'asset_category_id' => $assetCategory?->getKey(),
            'product_number' => $mappedRow->product_no,
            'product_description' => $mappedRow->description,
            'service_description' => $mappedRow->service_level_description,
            'serial_number' => $mappedRow->serial_no,
            'pricing_document' => $mappedRow->pricing_document,
            'system_handle' => $mappedRow->system_handle,
            'service_agreement_id' => $mappedRow->searchable,
            'base_warranty_start_date' => $dateFrom,
            'base_warranty_end_date' => $dateTo,
            'active_warranty_start_date' => $dateFrom,
            'active_warranty_end_date' => $dateTo,
            'unit_price' => $baseRate * (float)$mappedRow->original_price,
        ]);

    }

    protected function migrateAssetsFromWorldwidePackQuote(WorldwideQuoteVersion $quote): void
    {
        foreach ($quote->assets as $asset) {

            if (is_null($asset->vendor_id)) {
                continue;
            }

            $this->performMigrationOfGenericQuoteAssetData(
                $this->buildWorldwidePackQuoteAssetData($asset, $quote)
            );

        }
    }

    protected function buildWorldwidePackQuoteAssetData(WorldwideQuoteAsset $asset, WorldwideQuoteVersion $quote): QuoteAsset
    {
        $assetCategory = match (true) {
            (float)$asset->original_price === 0.0 => $this->getAssetCategory('Software'),
            default => $this->getAssetCategory('Hardware'),
        };

        /** @var Address|null $assetAddress */
        $assetAddress = $asset->machineAddress;

        $baseRate = value(function () use ($asset, $quote) {
            if (is_null($asset->buyCurrency)) {
                return 1.0;
            }

            return $this->exchangeRateService->getBaseRate(
                $asset->buyCurrency,
                $quote->{$quote->getCreatedAtColumn()}
            );
        });

        $dateFrom = transform($asset->expiry_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));
        $dateTo = transform($quote->worldwideQuote?->opportunity?->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        return new QuoteAsset([
            'user_id' => $quote->user_id,
            'address_id' => $assetAddress?->getKey(),
            'vendor_id' => $asset->vendor_id,
            'quote_id' => $quote->worldwideQuote->getKey(),
            'quote_type' => $quote->worldwideQuote->getMorphClass(),
            'company_reference_id' => $quote->worldwideQuote->opportunity->primaryAccount?->getKey(),
            'vendor_short_code' => $asset->vendor?->short_code,
            'asset_category_id' => $assetCategory?->getKey(),
            'product_number' => $asset->sku,
            'product_description' => $asset->product_name,
            'service_description' => $asset->service_level_description,
            'serial_number' => $asset->serial_no,
            'pricing_document' => null,
            'system_handle' => null,
            'service_agreement_id' => null,
            'base_warranty_start_date' => $dateFrom,
            'base_warranty_end_date' => $dateTo,
            'active_warranty_start_date' => $dateFrom,
            'active_warranty_end_date' => $dateTo,
            'unit_price' => $baseRate * (float)$asset->original_price,
        ]);
    }

    public function migrateAssetsFromRescueQuote(Quote $quote): void
    {
        $rows = $this->quoteQueries
            ->mappedOrderedRowsQuery($quote->activeVersionOrCurrent)
            ->lazyById()
            ->filter(function (object $row) {

                if (blank($row->product_no ?? null)) {
                    return false;
                }

                if (blank($row->serial_no ?? null)) {
                    return false;
                }

                return true;

            });

        foreach ($rows as $row) {

            $this->performMigrationOfGenericQuoteAssetData(
                $this->buildRescueQuoteAssetData($row, $quote)
            );

        }

        with($quote, function (Quote $quote) {

            $quote->assets_migrated_at = now();

            $quote->save();

        });

        $this->logger->info(ASSET_MGQF_01, [
            'quote_id' => $quote->getKey(),
            'quote_number' => $quote->customer?->rfq,
            'entity_class' => $quote::class,
        ]);
    }

    protected function buildRescueQuoteAssetData(object $row, Quote $quote): QuoteAsset
    {
        $assetCategory = $this->recognizeAssetCategory($row);
        $assetAddress = $this->resolveAddressForRescueQuoteAsset($quote, $assetCategory);

        $baseRate = value(function () use ($quote): float {

            if (is_null($quote->sourceCurrency)) {
                return 1.0;
            }

            return $this->exchangeRateService
                ->getBaseRate(
                    $quote->sourceCurrency,
                    $quote->{$quote->getCreatedAtColumn()}
                );

        });

        $company = $this->customerFlowService->migrateCustomer(
            $quote->customer
        );

        return new QuoteAsset([
            'user_id' => $quote->user_id,
            'address_id' => $assetAddress?->getKey(),
            'vendor_id' => $quote->vendor_id,
            'quote_id' => $quote->getKey(),
            'quote_type' => $quote->getMorphClass(),
            'company_reference_id' => $company->getKey(),
            'vendor_short_code' => $quote->vendor?->short_code,
            'asset_category_id' => $assetCategory?->getKey(),
            'product_number' => $row->product_no ?? null,
            'product_description' => $row->product_description ?? null,
            'service_description' => $row->service_level_description ?? null,
            'serial_number' => $row->serial_no ?? null,
            'pricing_document' => $row->pricing_document ?? null,
            'system_handle' => $row->system_handle ?? null,
            'service_agreement_id' => $row->searchable ?? null,
            'base_warranty_start_date' => transform($row->date_from ?? null, fn(string $date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay()),
            'base_warranty_end_date' => transform($row->date_to ?? null, fn(string $date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay()),
            'active_warranty_start_date' => transform($row->date_from ?? null, fn(string $date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay()),
            'active_warranty_end_date' => transform($row->date_to ?? null, fn(string $date) => Carbon::createFromFormat('d/m/Y', $date)->startOfDay()),
            'unit_price' => $baseRate * (float)($row->price ?? null),
        ]);
    }

    protected function performMigrationOfGenericQuoteAssetData(QuoteAsset $quoteAsset): ?Asset
    {
        $violations = $this->validator->validate($quoteAsset);

        if (count($violations)) {
            throw new ValidationFailedException($quoteAsset, $violations);
        }

        /** @var Asset $asset */
        $asset = Asset::query()
            ->where('user_id', $quoteAsset->user_id)
            ->where('vendor_id', $quoteAsset->vendor_id)
            ->where('address_id', $quoteAsset->address_id)
            ->where('product_number', $quoteAsset->product_number)
            ->where('serial_number', $quoteAsset->serial_number)
            ->where('quote_id', $quoteAsset->quote_id)
            ->where('quote_type', $quoteAsset->quote_type)
            ->firstOrNew();

        with($asset, function (Asset $asset) use ($quoteAsset) {

            if ($asset->exists) {
                return;
            }

            $asset->assetCategory()->associate($quoteAsset->asset_category_id);
            $asset->user()->associate($quoteAsset->user_id);
            $asset->vendor()->associate($quoteAsset->vendor_id);
            $asset->quote_id = $quoteAsset->quote_id;
            $asset->quote_type = $quoteAsset->quote_type;
            $asset->address()->associate($quoteAsset->address_id);

            $asset->product_number = $quoteAsset->product_number;
            $asset->serial_number = $quoteAsset->serial_number;
            $asset->vendor_short_code = $quoteAsset->vendor_short_code;
            $asset->product_description = $quoteAsset->product_description;
            $asset->service_description = $quoteAsset->service_description;
            $asset->base_warranty_start_date = $quoteAsset->base_warranty_start_date;
            $asset->base_warranty_end_date = $quoteAsset->base_warranty_end_date;
            $asset->active_warranty_start_date = $quoteAsset->active_warranty_start_date;
            $asset->active_warranty_end_date = $quoteAsset->active_warranty_end_date;

            $asset->unit_price = $quoteAsset->unit_price;
            $asset->is_migrated = true;

            $this->connection->transaction(fn() => $asset->save());

        });

        // Attach referenced company entity to the asset.
        with($asset, function (Asset $asset) use ($quoteAsset) {

            if (is_null($quoteAsset->company_reference_id)) {
                return;
            }

            $this->connection->transaction(function () use ($asset, $quoteAsset) {

                $asset->companies()->syncWithoutDetaching([$quoteAsset->company_reference_id]);

            });

        });

        return tap($asset, function (Asset $asset) {
            //
        });
    }

    protected function getAssetCategory(string $name): ?AssetCategory
    {
        if (isset($this->assetCategoryCache[$name])) {
            return $this->assetCategoryCache[$name];
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assetCategoryCache[$name] = AssetCategory::query()->where('name', $name)->first();
    }

    protected function recognizeAssetCategory(object $row)
    {
        if (optional($row)->price == 0) {
            return $this->getAssetCategory('Software');
        }

        return $this->getAssetCategory('Hardware');
    }

    protected function resolveAddressForRescueQuoteAsset(BaseQuote $quote, ?AssetCategory $assetCategory): ?Address
    {
        if (optional($assetCategory)->name === 'Software') {
            return $quote->customer->addresses->firstWhere('address_type', 'Software') ?? $quote->customer->addresses->first();
        }

        return $quote->customer->addresses->firstWhere('address_type', 'Hardware') ?? $quote->customer->addresses->first();
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}
