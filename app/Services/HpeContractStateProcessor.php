<?php

namespace App\Services;

use App\Contracts\Services\HpeContractState;
use App\DTO\{ImportResponse, HpeContractService, HpeContractAssetCollection, HpeContractServiceCollection, PreviewHpeContractData};
use App\Models\{HpeContract, HpeContractData, HpeContractFile};
use App\Models\Customer\Customer;
use App\Models\Quote\Contract;
use App\Scopes\ContractTypeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\{Arr, Carbon, Collection, Str, Facades\DB};

class HpeContractStateProcessor implements HpeContractState
{
    protected const CN_PATTERN = "EQ-HPE-%'.07d";

    protected HpeContract $hpeContract;

    public function __construct(HpeContract $hpeContract)
    {
        $this->hpeContract = $hpeContract;
    }

    /**
     * Process HPE Contract state.
     *
     * @param  array $state
     * @param  HpeContract|null $hpeContract
     * @return HpeContract
     */
    public function processState(array $state, ?HpeContract $hpeContract = null)
    {
        if (! isset($state['last_drafted_step'])) {
            $state['last_drafted_step'] = collect($this->hpeContract->getCompletenessDictionary())->sort()->keys()->first();
        }

        return DB::transaction(function () use ($state, $hpeContract) {
            /** @var HpeContract */
            $hpeContract ??= $this->initiateHpeContractInstance();

            tap($hpeContract->fill($state)->save(), fn () => $this->reflectHpeContractState($hpeContract));

            return $hpeContract;
        }, DB_TA);
    }

    /**
     * Intiate a new HPE Contract Instance with new sequence number.
     *
     * @return HpeContract
     */
    public function initiateHpeContractInstance(): HpeContract
    {
        $sequenceNumber = $this->getHighestNumber() + 1;

        return $this->hpeContract->query()->make()->forceFill([
            'contract_number' => static::giveContractNumber($sequenceNumber),
            'sequence_number' => $sequenceNumber,
        ]);
    }

    public function copy(HpeContract $hpeContract)
    {
        return DB::transaction(function () use ($hpeContract) {
            /** @var HpeContractFile */
            $newContractFile = tap($hpeContract->hpeContractFile->replicate(['user_id']))->save();

            /**
             * Replicating HPE Contract File Imported Data.
             */
            $contractFileKey = $newContractFile->getKey();

            $columns = (new HpeContractData)->getFillable();

            $replicateColumns = Collection::wrap([...$columns, ...['hpe_contract_file_id']])
                ->flip()
                ->flip()
                ->map(fn ($column) => $column === 'hpe_contract_file_id' ? DB::raw("'{$contractFileKey}' as hpe_contract_file_id") : $column)
                ->toArray();

            DB::table('hpe_contract_data')->insertUsing(
                $columns,
                $hpeContract->hpeContractFile->hpeContractData()->select($replicateColumns)->toBase()
            );

            /**
             * Replicating the HPE Contract Instance and association with the new one HPE Contract File.
             */
            $newHpeContract = tap(
                $hpeContract->replicate(['user_id', 'hpe_contract_file_id', 'hpeContractFile']),
                function (HpeContract $newHpeContract) use ($newContractFile) {
                    $newHpeContract->save();

                    $this->associateHpeContractFile($newHpeContract, $newContractFile);
                    $this->reflectHpeContractState($newHpeContract);
                }
            );

            /**
             * Finally we unravel the copyable contract.
             */
            tap($hpeContract->unsubmit(), fn () => $this->reflectHpeContractState($hpeContract));

            return [$newHpeContract->getKeyName() => $newHpeContract->getKey()];
        }, DB_TA);
    }

    /**
     * Submit the specified HPE Contract.
     *
     * @param  HpeContract $hpeContract
     * @return bool
     */
    public function submit(HpeContract $hpeContract): bool
    {
        if (!is_null($hpeContract->submitted_at)) {
            return false;
        }

        $hpeContract->forceFill(['last_drafted_step' => 'Complete']);

        return DB::transaction(fn () => tap(
            $hpeContract->submit(),
            fn () => $this->reflectHpeContractState($hpeContract)
        ), DB_TA);
    }

    /**
     * Unsubmit the specified HPE Contract.
     *
     * @param  HpeContract $hpeContract
     * @return bool
     */
    public function unsubmit(HpeContract $hpeContract): bool
    {
        if (is_null($hpeContract->submitted_at)) {
            return false;
        }

        return DB::transaction(fn () => tap(
            $hpeContract->unsubmit(),
            fn () => $this->reflectHpeContractState($hpeContract)
        ), DB_TA);
    }

    /**
     * Mark as activated the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function activate(HpeContract $hpeContract): bool
    {
        if (!is_null($hpeContract->activated_at)) {
            return false;
        }

        return DB::transaction(fn () => tap(
            $hpeContract->activate(),
            fn () => $this->reflectHpeContractState($hpeContract)
        ), DB_TA);
    }

    /**
     * Mark as deactivated the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function deactivate(HpeContract $hpeContract): bool
    {
        if (is_null($hpeContract->activated_at)) {
            return false;
        }

        return DB::transaction(fn () => tap(
            $hpeContract->deactivate(),
            fn () => $this->reflectHpeContractState($hpeContract)
        ), DB_TA);
    }

    /**
     * Delete the specified HPE Contract and it's respective reflection in the Quotes table.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function delete(HpeContract $hpeContract): bool
    {
        return DB::transaction(function () use ($hpeContract) {
            return $hpeContract->delete() && $hpeContract->contract()->delete();
        }, DB_TA);
    }

    /**
     * Associate HPE Contract with specified HPE Contract File.
     *
     * @param  HpeContract $hpeContract
     * @param  HpeContractFile $hpeContractFile
     * @return void
     */
    public function associateHpeContractFile(HpeContract $hpeContract, HpeContractFile $hpeContractFile): void
    {
        DB::transaction(fn () => $hpeContract->hpeContractFile()->associate($hpeContractFile)->save(), DB_TA);
    }

    /**
     * Process Imported HPE Contract Data.
     *
     * @param  HpeContract $hpeContract
     * @param  HpeContractFile $hpeContractFile
     * @param  ImportResponse|null $importResponse
     * @return void
     */
    public function processHpeContractData(
        HpeContract $hpeContract,
        HpeContractFile $hpeContractFile,
        ?ImportResponse $importResponse = null
    ): bool {
        if (isset($importResponse) && $importResponse->failed()) {
            return false;
        }

        return DB::transaction(function () use ($hpeContract, $hpeContractFile) {
            if ($hpeContract->hpeContractFile->isNot($hpeContractFile)) {
                $hpeContract->hpeContractFile->delete();
            }

            $this->associateHpeContractFile($hpeContract, $hpeContractFile);

            $services = $hpeContractFile->hpeContractData()
                ->getBaseQuery()
                ->selectRaw('MAX(service_levels) as service_levels')
                ->addSelect('contract_number', 'service_code', 'service_code_2', 'service_description', 'service_description_2')
                ->whereNotNull('service_description')
                ->whereNotNull('service_description_2')
                ->groupBy('service_description', 'service_description_2', 'contract_number')
                ->get();

            $services = static::parseServices($services);

            $contractNumbers = $hpeContractFile->hpeContractData()->toBase()
                ->groupBy('contract_number')
                ->select('contract_number', 'contract_start_date', 'contract_end_date', 'order_authorization')
                ->get()
                ->toArray();

            $ordersAuth = $hpeContractFile->hpeContractData()->whereNotNull('order_authorization')->distinct('order_authorization')->pluck('order_authorization');

            $contractData = (array) $hpeContractFile->hpeContractData()->toBase()
                ->selectRaw('MAX(amp_id) amp_id')
                ->selectRaw('MAX(support_account_reference) support_account_reference')
                ->first();

            $contacts = $this->retrieveCustomerContacts($hpeContract);

            $hpeContract->fill(array_merge($contractData, $contacts, [
                'contract_numbers'      => $contractNumbers,
                'orders_authorization'  => $ordersAuth,
                'services'              => $services,
            ]))->saveOrFail();

            return true;
        }, DB_TA);
    }

    /**
     * Retrieve and aggregate HPE Contract Data.
     *
     * @param HpeContract $hpeContract
     * @return mixed
     */
    public function retrieveContractData(HpeContract $hpeContract)
    {
        $contractData = $this->buildHpeContractAssetsQuery($hpeContract)->get();

        $contracts = Collection::wrap($hpeContract->contract_numbers)->keyBy('contract_number');

        return $contractData->groupBy('contract_number')->map(fn ($assets, $key) =>
        [
            'contract_number'       => $key,
            'contract_start_date'   => data_get($contracts, "{$key}.contract_start_date"),
            'contract_end_date'     => data_get($contracts, "{$key}.contract_end_date"),
            'order_authorization'   => data_get($contracts, "{$key}.order_authorization"),
            'assets'                => HpeContractAssetCollection::fromCollection($assets)->toArray()
        ])->values();
    }

    /**
     * Retrieve HPE Contract Assets grouped by Support Account Reference
     *
     * @param  HpeContract $hpeContract
     * @return PreviewHpeContractData
     */
    public function retrieveSummarizedContractData(HpeContract $hpeContract): PreviewHpeContractData
    {
        $assets = $this->buildHpeContractAssetsQuery($hpeContract)->whereIsSelected(true)->get();

        $contacts = [
            'hw_delivery_contact'       => $hpeContract->hw_delivery_contact,
            'sw_delivery_contact'       => $hpeContract->sw_delivery_contact,
            'pr_support_contact'        => $hpeContract->pr_support_contact,
            'entitled_party_contact'    => $hpeContract->entitled_party_contact,
            'end_customer_contact'      => $hpeContract->end_customer_contact,
            'sold_contact'              => $hpeContract->sold_contact,
            'bill_contact'              => $hpeContract->bill_contact,
        ];

        $contracts = $assets->groupBy('contract_number')->sortKeys();

        $contractsData = Collection::wrap($hpeContract->contract_numbers)->keyBy('contract_number')->map(function ($contract) {
            $contract = collect($contract);
            $dates = $contract->only('contract_start_date', 'contract_end_date')->map(fn ($date) => transform($date, fn ($date) => Carbon::parse($date)->format(config('date.format_ui'))));

            return $contract->merge($dates)->toArray();
        });

        $services = Collection::wrap($hpeContract->services)->keyBy('service_code_2');

        $contractServices = $services->values()->groupBy('contract_number');

        $allContractServices = $services->values()
            ->sortBy('contract_number')
            ->filter(fn (HpeContractService $service) => $contractsData->has($service->contract_number))
            ->map(function (HpeContractService $service) use ($contractsData) {
                /** @var array */
                $contract = $contractsData->get($service->contract_number);

                return [
                    'contract_number'       => $service->contract_number,
                    'contract_start_date'   => $contract['contract_start_date'] ?? null,
                    'contract_end_date'     => $contract['contract_end_date'] ?? null,
                    'service_description_2' => $service->service_description_2,
                ];
            });

        $sars = $assets->groupBy('support_account_reference')->map(fn ($assets, $key) => [
            'support_account_reference' => $key,
            'assets' => HpeContractAssetCollection::fromCollection($assets)->items()
        ])->values();

        $contractAssets = $contracts
            ->filter(fn ($assets, $contract) => $contractsData->has($contract))
            ->map(function ($assets, $contractNumber) use ($contractsData, $services, $contractServices) {
                $assetsWithinServices = $assets->groupBy('service_code_2')
                    ->filter(fn ($assets, $serviceCode) => $services->has($serviceCode))
                    ->map(function (Collection $assets, $serviceCode) use ($services) {
                        /** @var \App\DTO\HpeContractService */
                        $service = $services->get($serviceCode);

                        return [
                            'service_code'          => $service->service_code_2,
                            'service_description'   => $service->service_description,
                            'service_description_2' => $service->service_description_2,
                            'assets'                => HpeContractAssetCollection::fromCollection($assets)->items()
                        ];
                    })->values();

                /** @var array */
                $contract = $contractsData->get($contractNumber);

                $supportServiceLevels = $contractServices->get($contractNumber);

                return [
                    'contract_number'        => (string) $contractNumber,
                    'contract_start_date'    => $contract['contract_start_date'] ?? null,
                    'contract_end_date'      => $contract['contract_end_date'] ?? null,
                    'order_authorization'    => $contract['order_authorization'] ?? null,
                    'assets_services'        => $assetsWithinServices,
                    'support_service_levels' => HpeContractServiceCollection::fromCollection($supportServiceLevels)->items(),
                ];
            })->values();

        $serialNumbers = $assets->groupBy('contract_number')
            ->map(fn (Collection $assets, $contractNumber) =>
            [
                'contract_number'   => (string) $contractNumber,
                'assets'            => HpeContractAssetCollection::fromCollection($assets->sortBy('serial_number')->values())->items()
            ])
            ->sortKeys()
            ->values();

        $contractDetails = $contractsData
            ->map(fn ($contract) => array_merge($contract, ['hpe_sales_order_no' => $hpeContract->hpe_sales_order_no]))
            ->values();

        $supportServices = HpeContractServiceCollection::fromCollection($services->values())->toBaseCollection()
            ->groupBy('contract_number')->map(fn (Collection $services, $contract) => [
                'contract_number' => $contract,
                'services' => $services,
            ])
            ->values();

        return new PreviewHpeContractData(
            array_merge([
                'amp_id'                        => $hpeContract->amp_id,
                'contract_number'               => $hpeContract->contract_number,

                'contract_details'              => $contractDetails,

                'contract_assets'               => $contractAssets,

                'support_services'              => $supportServices,

                'purchase_order_date'           => optional($hpeContract->purchase_order_date)->format(config('date.format_ui')),
                'purchase_order_no'             => $hpeContract->purchase_order_no,
                'hpe_sales_order_no'            => $hpeContract->hpe_sales_order_no,

                'contract_date'                 => optional($hpeContract->contract_date)->format(config('date.format_ui')),

                'service_overview'              => $allContractServices->values(),
                'support_account_reference'     => $sars,
                'asset_locations'               => $assets,
                'serial_numbers'                => $serialNumbers,
            ], $contacts)
        );
    }

    /**
     * Mark as selected/unselected HPE Contract assets.
     *
     * @param HpeContract $hpeContract
     * @param array $ids
     * @param boolean $reject
     * @return bool
     */
    public function selectAssets(HpeContract $hpeContract, array $ids, bool $reject = false)
    {
        return DB::transaction(function () use ($hpeContract, $ids, $reject) {
            $assetsQuery = $hpeContract->hpeContractData()->getQuery();

            (clone $assetsQuery)
                ->update(['is_selected' => false]);

            (clone $assetsQuery)->when(
                $reject,
                fn (Builder $query) => $query->whereKeyNot($ids),
                fn (Builder $query) => $query->whereKey($ids),
            )
                ->update(['is_selected' => true]);

            $hpeContract->touch();

            return true;
        }, DB_TA);
    }

    protected function reflectHpeContractState(HpeContract $hpeContract): Contract
    {
        $reflect = $hpeContract->contract()->getQuery()->withoutGlobalScope(ContractTypeScope::class)
            ->firstOrNew(['document_type' => Q_TYPE_HPE_CONTRACT]);

        $reflect->timestamps = false;

        /** Set Customer relation instance to cache in schemaless attributes. */
        $reflect->setRelation('customer', Customer::make(['rfq' => $hpeContract->contract_number, 'name' => $hpeContract->sold_contact->org_name]));

        $reflect->forceFill([
            'hpe_contract_id'            => $hpeContract->getKey(),
            'document_type'              => Q_TYPE_HPE_CONTRACT,
            'user_id'                    => $hpeContract->user_id,
            'company_id'                 => $hpeContract->company_id,
            'quote_template_id'          => $hpeContract->quote_template_id,
            'hpe_contract_number'        => $hpeContract->contract_number,
            'hpe_contract_customer_name' => $hpeContract->sold_contact->org_name,
            'last_drafted_step'          => $hpeContract->last_drafted_step,
            'created_at'                 => $hpeContract->getRawOriginal('created_at'),
            'updated_at'                 => $hpeContract->getRawOriginal('updated_at'),
            'submitted_at'               => $hpeContract->getRawOriginal('submitted_at'),
            'activated_at'               => $hpeContract->getRawOriginal('activated_at'),
        ]);

        return tap($reflect, function (Contract $reflect) {
            $reflect->save();
            $reflect->timestamps = true;
        });
    }

    protected function buildHpeContractAssetsQuery(HpeContract $hpeContract): Builder
    {
        return $hpeContract->hpeContractData()
            ->getQuery()
            ->whereIn('asset_type', ['Hardware', 'Software'])
            ->whereNotNull('serial_number')
            ->addSelect(
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
                'is_selected'
            )
            ->withCasts(['is_selected' => 'boolean']);
    }

    protected function getHighestNumber(): int
    {
        return (int) $this->hpeContract->query()->max('sequence_number');
    }

    protected function retrieveCustomerContacts(HpeContract $hpeContract): array
    {
        $contractData = (array) $hpeContract->hpeContractData()->toBase()
            ->selectRaw('MAX(customer_name) customer_name')
            ->selectRaw('MAX(customer_address) customer_address')
            ->selectRaw('MAX(customer_city) customer_city')
            ->selectRaw('MAX(customer_post_code) customer_post_code')
            ->selectRaw('MAX(customer_state_code) customer_state_code')

            ->selectRaw('MAX(reseller_name) reseller_name')
            ->selectRaw('MAX(reseller_address) reseller_address')
            ->selectRaw('MAX(reseller_city) reseller_city')
            ->selectRaw('MAX(reseller_post_code) reseller_post_code')
            ->selectRaw('MAX(reseller_state) reseller_state')

            ->selectRaw('MAX(hw_delivery_contact_name) hw_delivery_contact_name')
            ->selectRaw('MAX(hw_delivery_contact_phone) hw_delivery_contact_phone')
            ->selectRaw('MAX(sw_delivery_contact_name) sw_delivery_contact_name')
            ->selectRaw('MAX(sw_delivery_contact_phone) sw_delivery_contact_phone')
            ->selectRaw('MAX(pr_support_contact_name) pr_support_contact_name')
            ->selectRaw('MAX(pr_support_contact_phone) pr_support_contact_phone')
            ->first();

        $country = data_get(auth()->user(), 'country.name');

        $resellerContact = [
            'org_name'      => Arr::get($contractData, 'reseller_name'),
            'address'       => Arr::get($contractData, 'reseller_address'),
            'city'          => Arr::get($contractData, 'reseller_city'),
            'post_code'     => Arr::get($contractData, 'reseller_post_code'),
            'state'         => Arr::get($contractData, 'reseller_state'),
            'country'       => $country,
        ];

        $endUserContact = [
            'org_name'      => Arr::get($contractData, 'customer_name'),
            'address'       => Arr::get($contractData, 'customer_address'),
            'city'          => Arr::get($contractData, 'customer_city'),
            'post_code'     => Arr::get($contractData, 'customer_post_code'),
            'state_code'    => Arr::get($contractData, 'customer_state_code'),
            'country'       => $country,
        ];

        $contacts = [
            'sold_contact'              => $resellerContact,
            'bill_contact'              => $resellerContact,
            'hw_delivery_contact'       => ['attn' => Arr::get($contractData, 'hw_delivery_contact_name'), 'phone' => Arr::get($contractData, 'hw_delivery_contact_phone')],
            'sw_delivery_contact'       => ['attn' => Arr::get($contractData, 'sw_delivery_contact_name'), 'phone' => Arr::get($contractData, 'sw_delivery_contact_phone')],
            'pr_support_contact'        => ['attn' => Arr::get($contractData, 'pr_support_contact_name'), 'phone' => Arr::get($contractData, 'pr_support_contact_phone')],
            'entitled_party_contact'    => $endUserContact,
            'end_customer_contact'      => $endUserContact,
        ];

        return $contacts;
    }

    public static function giveContractNumber(int $sequenceNumber): string
    {
        return sprintf(static::CN_PATTERN, $sequenceNumber);
    }

    private static function parseServices($services): array
    {
        return Collection::wrap($services)->map(
            fn ($service) =>
            [
                'service_levels'            => Str::of(data_get($service, 'service_levels'))->explode(';')->filter(fn ($v) => filled($v))->values()->toArray(),
                'service_description'       => data_get($service, 'service_description'),
                'service_description_2'     => data_get($service, 'service_description_2'),
                'contract_number'           => data_get($service, 'contract_number'),
                'service_code'              => data_get($service, 'service_code'),
                'service_code_2'            => data_get($service, 'service_code_2'),
            ]
        )
            ->toArray();
    }
}
