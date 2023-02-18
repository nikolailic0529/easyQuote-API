<?php

namespace App\Domain\HpeContract\Services;

use App\Domain\HpeContract\Contracts\HpeContractState;
use App\Domain\HpeContract\DataTransferObjects\HpeContractAssetCollection;
use App\Domain\HpeContract\DataTransferObjects\HpeContractService;
use App\Domain\HpeContract\DataTransferObjects\HpeContractServiceCollection;
use App\Domain\HpeContract\DataTransferObjects\PreviewHpeContractData;
use App\Domain\HpeContract\DataTransferObjects\{ImportResponse};
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\HpeContract\Models\{HpeContractFile};
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Webpatser\Uuid\Uuid;

class HpeContractStateProcessor implements HpeContractState
{
    const CN_PATTERN = "EQ-HPE-%'.07d";

    public function __construct(protected ConnectionInterface $connection)
    {
    }

    /**
     * Process HPE Contract state.
     *
     * @throws \Throwable
     */
    public function processState(array $state, ?HpeContract $hpeContract = null): HpeContract
    {
        if (!isset($state['last_drafted_step'])) {
            $state['last_drafted_step'] = collect((new HpeContract())->getCompletenessDictionary())->sort()->keys()->first();
        }

        return tap($hpeContract ?? $this->initiateHpeContractInstance(), function (HpeContract $contract) use ($state) {
            $contract->fill($state);

            $this->connection->transaction(fn () => $contract->save());
        });
    }

    /**
     * Initiate a new HPE Contract Instance with new sequence number.
     */
    public function initiateHpeContractInstance(): HpeContract
    {
        $sequenceNumber = $this->getHighestNumber() + 1;

        return tap(new HpeContract(), function (HpeContract $contract) use ($sequenceNumber) {
            $contract->contract_number = self::buildContractNumber($sequenceNumber);
            $contract->sequence_number = $sequenceNumber;
        });
    }

    public function copy(HpeContract $hpeContract): array
    {
        /** @var \App\Domain\HpeContract\Models\HpeContractFile $newContractFile */
        $newContractFile = tap($hpeContract->hpeContractFile->replicate(['user_id']), function (HpeContractFile $contractFile) {
            $contractFile->{$contractFile->getKeyName()} = (string) Uuid::generate(4);
        });

        /**
         * Replicating HPE Contract File Imported Data.
         */
        $replicateColumns = [
            'hpe_contract_file_id' => new Expression("'{$newContractFile->getKey()}' as hpe_contract_file_id"),
            'amp_id' => 'amp_id',
            'support_account_reference' => 'support_account_reference',
            'contract_number' => 'contract_number',
            'order_authorization' => 'order_authorization',
            'contract_start_date' => 'contract_start_date',
            'contract_end_date' => 'contract_end_date',
            'price' => 'price',
            'product_number' => 'product_number',
            'serial_number' => 'serial_number',
            'product_description' => 'product_description',
            'product_quantity' => 'product_quantity',
            'asset_type' => 'asset_type',
            'service_type' => 'service_type',
            'service_code' => 'service_code',
            'service_description' => 'service_description',
            'service_code_2' => 'service_code_2',
            'service_description_2' => 'service_description_2',
            'service_levels' => 'service_levels',
            'hw_delivery_contact_name' => 'hw_delivery_contact_name',
            'hw_delivery_contact_phone' => 'hw_delivery_contact_phone',
            'sw_delivery_contact_name' => 'sw_delivery_contact_name',
            'sw_delivery_contact_phone' => 'sw_delivery_contact_phone',
            'pr_support_contact_name' => 'pr_support_contact_name',
            'pr_support_contact_phone' => 'pr_support_contact_phone',
            'customer_name' => 'customer_name',
            'customer_address' => 'customer_address',
            'customer_city' => 'customer_city',
            'customer_post_code' => 'customer_post_code',
            'customer_state_code' => 'customer_state_code',
            'reseller_name' => 'reseller_name',
            'reseller_address' => 'reseller_address',
            'reseller_city' => 'reseller_city',
            'reseller_state' => 'reseller_state',
            'reseller_post_code' => 'reseller_post_code',
            'support_start_date' => 'support_start_date',
            'support_end_date' => 'support_end_date',
            'is_selected' => 'is_selected',
        ];

        $this->connection->transaction(function () use ($hpeContract, $newContractFile, $replicateColumns) {
            $newContractFile->save();

            $this->connection->table('hpe_contract_data')->insertUsing(
                columns: array_keys($replicateColumns),
                query: $hpeContract->hpeContractFile->hpeContractData()
                    ->select(array_values($replicateColumns))
                    ->toBase()
            );
        });

        /**
         * Replicating the HPE Contract Instance and association with the new one HPE Contract File.
         */
        $newHpeContract = tap(
            $hpeContract->replicate(['user_id', 'hpe_contract_file_id', 'hpeContractFile', 'is_active']),
            function (HpeContract $newHpeContract) use ($newContractFile) {
                $sequenceNumber = $this->getHighestNumber() + 1;

                $newHpeContract->contract_number = self::buildContractNumber($sequenceNumber);
                $newHpeContract->sequence_number = $sequenceNumber;
                $newHpeContract->submitted_at = null;

                $newHpeContract->hpeContractFile()->associate($newContractFile);
            }
        );

        $hpeContract->activated_at = null;

        $this->connection->transaction(function () use ($hpeContract, $newHpeContract) {
            $newHpeContract->save();
            $hpeContract->save();
        });

        return [$newHpeContract->getKeyName() => $newHpeContract->getKey()];
    }

    /**
     * Submit the specified HPE Contract.
     *
     * @throws \Throwable
     */
    public function submit(HpeContract $hpeContract): bool
    {
        if (!is_null($hpeContract->submitted_at)) {
            return false;
        }

        $hpeContract->last_drafted_step = 'Complete';
        $hpeContract->submitted_at = now();

        $this->connection->transaction(fn () => $hpeContract->save());

        activity()->on($hpeContract)->queue('submitted');

        return true;
    }

    /**
     * Unravel the specified HPE Contract.
     *
     * @throws \Throwable
     */
    public function unravel(HpeContract $hpeContract): bool
    {
        if (is_null($hpeContract->submitted_at)) {
            return false;
        }

        $hpeContract->submitted_at = null;

        $this->connection->transaction(fn () => $hpeContract->save());

        activity()->on($hpeContract)->queue('unravel');

        return true;
    }

    /**
     * Mark as activated the specified HPE Contract.
     *
     * @throws \Throwable
     */
    public function activate(HpeContract $hpeContract): bool
    {
        if (!is_null($hpeContract->activated_at)) {
            return false;
        }

        $hpeContract->activated_at = now();

        $this->connection->transaction(fn () => $hpeContract->save());

        activity()->on($hpeContract)->queue('activated');

        return true;
    }

    /**
     * Mark as deactivated the specified HPE Contract.
     *
     * @throws \Throwable
     */
    public function deactivate(HpeContract $hpeContract): bool
    {
        if (is_null($hpeContract->activated_at)) {
            return false;
        }

        $hpeContract->activated_at = null;

        $this->connection->transaction(fn () => $hpeContract->save());

        activity()->on($hpeContract)->queue('deactivated');

        return true;
    }

    /**
     * Delete the specified HPE Contract.
     *
     * @throws \Throwable
     */
    public function delete(HpeContract $hpeContract): bool
    {
        return tap(true, function () use ($hpeContract) {
            $this->connection->transaction(fn () => $hpeContract->delete());
        });
    }

    /**
     * Process Imported HPE Contract Data.
     *
     * @param \App\Domain\HpeContract\Models\HpeContractFile $hpeContractFile
     *
     * @throws \Throwable
     */
    public function processHpeContractData(
        HpeContract $hpeContract,
        HpeContractFile $hpeContractFile,
        ?ImportResponse $importResponse = null
    ): bool {
        if (isset($importResponse) && $importResponse->failed()) {
            return false;
        }

        if ($hpeContract->hpeContractFile->isNot($hpeContractFile)) {
            $this->connection->transaction(fn () => $hpeContract->hpeContractFile->delete());
        }

        $hpeContract->hpeContractFile()->associate($hpeContractFile);

        $services = $this->pickServicesFromHpeContractFileData($hpeContractFile);
        $contractNumbers = $this->pickContractNumbersFromHpeContractFileData($hpeContractFile);
        $ordersAuth = $this->pickOrderAuthorizationFromHpeContractFileData($hpeContractFile);
        $contractData = $this->pickContractDataFromHpeContractFileData($hpeContractFile);

        $contacts = $this->pickCustomerContactsFromContractData($hpeContract);

        $hpeContract->fill(
            array_merge($contractData, $contacts, [
                'contract_numbers' => $contractNumbers,
                'orders_authorization' => $ordersAuth,
                'services' => $services,
            ])
        );

        return tap(true, function () use ($hpeContract) {
            $this->connection->transaction(fn () => $hpeContract->save());
        });
    }

    protected function pickContractDataFromHpeContractFileData(HpeContractFile $hpeContractFile): array
    {
        return (array) $hpeContractFile->hpeContractData()->toBase()
            ->selectRaw('MAX(amp_id) amp_id')
            ->selectRaw('MAX(support_account_reference) support_account_reference')
            ->first();
    }

    protected function pickOrderAuthorizationFromHpeContractFileData(HpeContractFile $hpeContractFile): array
    {
        return $hpeContractFile->hpeContractData()
            ->whereNotNull('order_authorization')
            ->distinct('order_authorization')
            ->pluck('order_authorization')
            ->all();
    }

    protected function pickContractNumbersFromHpeContractFileData(HpeContractFile $hpeContractFile): array
    {
        return $hpeContractFile->hpeContractData()
            ->toBase()
            ->groupBy('contract_number')
            ->select('contract_number', 'contract_start_date', 'contract_end_date', 'order_authorization')
            ->get()
            ->toArray();
    }

    protected function pickServicesFromHpeContractFileData(HpeContractFile $hpeContractFile): array
    {
        $services = $hpeContractFile->hpeContractData()
            ->getBaseQuery()
            ->selectRaw('service_levels')
            ->addSelect('contract_number', 'service_code', 'service_code_2', 'service_description', 'service_description_2')
            ->whereIn('id', fn (BaseBuilder $query) => $query->selectRaw('MIN(id)')->from('hpe_contract_data')
                ->where('hpe_contract_file_id', $hpeContractFile->getKey())
                ->whereNotNull('service_description')
                ->whereNotNull('service_description_2')
                ->groupBy('contract_number', 'service_code_2'))
            ->get();

        return BaseCollection::wrap($services)->map(
            fn ($service) => [
                'service_levels' => Str::of(data_get($service, 'service_levels'))->explode(';')->filter(fn ($v) => filled($v))->values()->toArray(),
                'service_description' => data_get($service, 'service_description'),
                'service_description_2' => data_get($service, 'service_description_2'),
                'contract_number' => data_get($service, 'contract_number'),
                'service_code' => data_get($service, 'service_code'),
                'service_code_2' => data_get($service, 'service_code_2'),
            ]
        )
            ->all();
    }

    /**
     * Retrieve and aggregate HPE Contract Data.
     */
    public function retrieveContractData(HpeContract $hpeContract): BaseCollection
    {
        $contractData = $this->buildHpeContractAssetsQuery($hpeContract)->get();

        $contracts = BaseCollection::wrap($hpeContract->contract_numbers)->keyBy('contract_number');

        return $contractData->groupBy('contract_number')->map(fn ($assets, $key) => [
            'contract_number' => $key,
            'contract_start_date' => data_get($contracts, "{$key}.contract_start_date"),
            'contract_end_date' => data_get($contracts, "{$key}.contract_end_date"),
            'order_authorization' => data_get($contracts, "{$key}.order_authorization"),
            'assets' => HpeContractAssetCollection::fromCollection($assets)->toArray(),
        ])->values();
    }

    /**
     * Retrieve HPE Contract Assets grouped by Support Account Reference.
     */
    public function retrieveSummarizedContractData(HpeContract $hpeContract): PreviewHpeContractData
    {
        $assets = $this->buildHpeContractAssetsQuery($hpeContract)->whereIsSelected(true)->get();

        $contacts = [
            'hw_delivery_contact' => $hpeContract->hw_delivery_contact,
            'sw_delivery_contact' => $hpeContract->sw_delivery_contact,
            'pr_support_contact' => $hpeContract->pr_support_contact,
            'entitled_party_contact' => $hpeContract->entitled_party_contact,
            'end_customer_contact' => $hpeContract->end_customer_contact,
            'sold_contact' => $hpeContract->sold_contact,
            'bill_contact' => $hpeContract->bill_contact,
        ];

        $contracts = $assets->groupBy('contract_number')->sortKeys();

        $contractsData = BaseCollection::wrap($hpeContract->contract_numbers)->keyBy('contract_number')->map(function ($contract) {
            $contract = collect($contract);
            $dates = $contract->only('contract_start_date', 'contract_end_date')
                ->map(fn ($date) => transform($date, fn ($date) => Carbon::parse($date)->format(config('date.format_eu'))));

            return $contract->merge($dates)->toArray();
        });

        $services = BaseCollection::wrap($hpeContract->services)->keyBy('service_code_2');

        $contractServices = $services->values()->groupBy('contract_number');

        $allContractServices = $services->values()
            ->sortBy('contract_number')
            ->filter(fn (HpeContractService $service) => $contractsData->has($service->contract_number))
            ->map(function (HpeContractService $service) use ($contractsData) {
                /** @var array */
                $contract = $contractsData->get($service->contract_number);

                return [
                    'contract_number' => $service->contract_number,
                    'contract_start_date' => $contract['contract_start_date'] ?? null,
                    'contract_end_date' => $contract['contract_end_date'] ?? null,
                    'service_description_2' => $service->service_description_2,
                ];
            });

        $sars = $assets->groupBy('support_account_reference')->map(fn ($assets, $key) => [
            'support_account_reference' => $key,
            'assets' => HpeContractAssetCollection::fromCollection($assets)->items(),
        ])->values();

        $contractAssets = $contracts
            ->filter(fn ($assets, $contract) => $contractsData->has($contract))
            ->map(function ($assets, $contractNumber) use ($contractsData, $services, $contractServices) {
                $assetsWithinServices = $assets->groupBy('service_code_2')
                    // ->filter(fn ($assets, $serviceCode) => $services->has($serviceCode))
                    ->map(function (BaseCollection $assets, $serviceCode) use ($services) {
                        /** @var \App\Domain\HpeContract\DataTransferObjects\HpeContractService */
                        $service = $services->get($serviceCode);

                        return [
                            'service_code' => $assets->first()['service_code_2'] ?? null,
                            'service_description' => $assets->first()['service_description'] ?? null,
                            'service_description_2' => $assets->first()['service_description_2'] ?? null,
                            'assets' => HpeContractAssetCollection::fromCollection($assets)->items(),
                        ];
                    })->values();

                /** @var array */
                $contract = $contractsData->get($contractNumber);

                $supportServiceLevels = BaseCollection::wrap($contractServices->get($contractNumber))->filter();

                return [
                    'contract_number' => (string) $contractNumber,
                    'contract_start_date' => $contract['contract_start_date'] ?? null,
                    'contract_end_date' => $contract['contract_end_date'] ?? null,
                    'order_authorization' => $contract['order_authorization'] ?? null,
                    'assets_services' => $assetsWithinServices,
                    'support_service_levels' => HpeContractServiceCollection::fromCollection($supportServiceLevels)->items(),
                ];
            })->values();

        $serialNumbers = $assets->groupBy('contract_number')
            ->map(fn (BaseCollection $assets, $contractNumber) => [
                'contract_number' => (string) $contractNumber,
                'assets' => HpeContractAssetCollection::fromCollection($assets->sortBy('serial_number')->values())->items(),
            ])
            ->sortKeys()
            ->values();

        $contractDetails = $contractsData
            ->map(fn ($contract) => array_merge($contract, ['hpe_sales_order_no' => $hpeContract->hpe_sales_order_no]))
            ->values();

        $supportServices = HpeContractServiceCollection::fromCollection($services->values())->toBaseCollection()
            ->groupBy('contract_number')->map(fn (BaseCollection $services, $contract) => [
                'contract_number' => $contract,
                'services' => $services,
            ])
            ->values();

        return new PreviewHpeContractData(
            array_merge([
                'amp_id' => $hpeContract->amp_id,
                'contract_number' => $hpeContract->contract_number,

                'contract_details' => $contractDetails,

                'contract_assets' => $contractAssets,

                'support_services' => $supportServices,

                'purchase_order_date' => optional($hpeContract->purchase_order_date)->format(config('date.format_eu')),
                'purchase_order_no' => $hpeContract->purchase_order_no,
                'hpe_sales_order_no' => $hpeContract->hpe_sales_order_no,

                'contract_date' => optional($hpeContract->contract_date)->format(config('date.format_eu')),

                'service_overview' => $allContractServices->values(),
                'support_account_reference' => $sars,
                'asset_locations' => $assets,
                'serial_numbers' => $serialNumbers,
            ], $contacts)
        );
    }

    /**
     * Mark as selected/unselected HPE Contract assets.
     *
     * @throws \Throwable
     */
    public function markAssetsAsSelected(HpeContract $hpeContract, array $ids, bool $reject = false): bool
    {
        return tap(true, function () use ($ids, $reject, $hpeContract) {
            $assetsQuery = $hpeContract->hpeContractData()->getQuery();

            $this->connection->transaction(function () use ($hpeContract, $ids, $reject, $assetsQuery) {
                (clone $assetsQuery)
                    ->update(['is_selected' => false]);

                (clone $assetsQuery)->when(
                    $reject,
                    fn (Builder $query) => $query->whereKeyNot($ids),
                    fn (Builder $query) => $query->whereKey($ids),
                )
                    ->update(['is_selected' => true]);

                $hpeContract->touch();
            });
        });
    }

    protected function buildHpeContractAssetsQuery(HpeContract $hpeContract): Builder
    {
        return $hpeContract->hpeContractData()
            ->getQuery()
            ->whereIn('asset_type', ['Environmental Service', 'Hardware', 'Software', 'JW'])
            ->whereNotNull('product_number')
            ->addSelect([
                'id',
                'support_account_reference',
                'contract_number',
                'product_number',
                'serial_number',
                'product_description',
                'product_quantity',
                'asset_type',
                'service_type',
                'service_code',
                'service_description',
                'service_code_2',
                'service_description_2',
                'service_levels',
                'support_start_date',
                'support_end_date',
                'is_selected',
            ])
            ->withCasts(['is_selected' => 'boolean']);
    }

    protected function getHighestNumber(): int
    {
        return (int) HpeContract::query()->max('sequence_number');
    }

    #[ArrayShape(['sold_contact' => 'array', 'bill_contact' => 'array', 'hw_delivery_contact' => 'array', 'sw_delivery_contact' => 'array', 'pr_support_contact' => 'array', 'entitled_party_contact' => 'array', 'end_customer_contact' => 'array'])]
    protected function pickCustomerContactsFromContractData(HpeContract $hpeContract): array
    {
        $columns = [
            'customer_name',
            'customer_address',
            'customer_city',
            'customer_post_code',
            'customer_state_code',
            'reseller_name',
            'reseller_address',
            'reseller_city',
            'reseller_post_code',
            'reseller_state',
            'hw_delivery_contact_name',
            'hw_delivery_contact_phone',
            'sw_delivery_contact_name',
            'sw_delivery_contact_phone',
            'pr_support_contact_name',
            'pr_support_contact_phone',
        ];

        $contractData = $hpeContract->hpeContractData()
            ->select($columns)
            ->distinct()
            ->toBase()
            ->get();

        $pickedData = $contractData->reduce(function (array $result, object $item) use ($columns) {
            foreach ($columns as $column) {
                if (isset($result[$column])) {
                    continue;
                }

                $value = trim((string) data_get($item, $column));

                if ($value !== '') {
                    $result[$column] = $value;
                }
            }

            return $result;
        }, []);

        $country = data_get(auth()->user(), 'country.name');

        $resellerContact = [
            'org_name' => Arr::get($pickedData, 'reseller_name'),
            'address' => Arr::get($pickedData, 'reseller_address'),
            'city' => Arr::get($pickedData, 'reseller_city'),
            'post_code' => Arr::get($pickedData, 'reseller_post_code'),
            'state' => Arr::get($pickedData, 'reseller_state'),
            'country' => $country,
        ];

        $endUserContact = [
            'org_name' => Arr::get($pickedData, 'customer_name'),
            'address' => Arr::get($pickedData, 'customer_address'),
            'city' => Arr::get($pickedData, 'customer_city'),
            'post_code' => Arr::get($pickedData, 'customer_post_code'),
            'state_code' => Arr::get($pickedData, 'customer_state_code'),
            'country' => $country,
        ];

        return [
            'sold_contact' => $resellerContact,
            'bill_contact' => $resellerContact,
            'hw_delivery_contact' => ['attn' => Arr::get($pickedData, 'hw_delivery_contact_name'), 'phone' => Arr::get($pickedData, 'hw_delivery_contact_phone')],
            'sw_delivery_contact' => ['attn' => Arr::get($pickedData, 'sw_delivery_contact_name'), 'phone' => Arr::get($pickedData, 'sw_delivery_contact_phone')],
            'pr_support_contact' => ['attn' => Arr::get($pickedData, 'pr_support_contact_name'), 'phone' => Arr::get($pickedData, 'pr_support_contact_phone')],
            'entitled_party_contact' => $endUserContact,
            'end_customer_contact' => $endUserContact,
        ];
    }

    public static function buildContractNumber(int $sequenceNumber): string
    {
        return sprintf(static::CN_PATTERN, $sequenceNumber);
    }
}
