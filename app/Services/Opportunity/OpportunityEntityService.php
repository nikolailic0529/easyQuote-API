<?php

namespace App\Services\Opportunity;

use App\DTO\{Opportunity\BatchOpportunityUploadResult,
    Opportunity\BatchSaveOpportunitiesData,
    Opportunity\CreateOpportunityData,
    Opportunity\CreateSupplierData,
    Opportunity\ImportedOpportunityData,
    Opportunity\MarkOpportunityAsLostData,
    Opportunity\UpdateOpportunityData,
    Opportunity\UpdateSupplierData
};
use App\Enum\Lock;
use App\Enum\OpportunityStatus;
use App\Events\{Opportunity\OpportunityCreated,
    Opportunity\OpportunityDeleted,
    Opportunity\OpportunityMarkedAsLost,
    Opportunity\OpportunityMarkedAsNotLost,
    Opportunity\OpportunityUpdated
};
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Timezone;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\User;
use App\Services\Exceptions\ValidationException;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class OpportunityEntityService
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected ValidatorInterface $validator;

    protected EventDispatcher $eventDispatcher;

    protected ValidatorFactory $validatorFactory;

    private array $accountOwnerCache = [];

    public function __construct(ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                ValidatorInterface $validator,
                                EventDispatcher $eventDispatcher,
                                ValidatorFactory $validatorFactory)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->validatorFactory = $validatorFactory;
    }

    public function batchSaveOpportunities(BatchSaveOpportunitiesData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->opportunities as $opportunity) {

            $this->connection->transaction(fn() => $opportunity->restore());

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($opportunity)
            );

        }
    }

    /**
     * Perform batch Opportunity import.
     *
     * @param UploadedFile $fileInfo
     * @param User $user
     * @return BatchOpportunityUploadResult
     * @throws ValidationException
     * @throws \Throwable
     */
    public function batchImportOpportunities(UploadedFile $fileInfo, User $user): BatchOpportunityUploadResult
    {
        $reader = (new OpportunityBatchFileReader(
            $fileInfo->getRealPath(),
            $fileInfo->getClientOriginalExtension()
        ));

        $errors = (new MessageBag())
            ->setFormat('Validation failure on :key row. :message');

        $imported = [];

        foreach ($reader->getRows() as $i => $row) {
            $validator = $this->validateBatchOpportunityRow($row);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $errors->add($i + 1, $error);
                }

                continue;
            }

            $importedOpportunity = $this->importOpportunity($this->mapBatchOpportunityRow($row, $user));

            $imported[] = new ImportedOpportunityData([
                'id' => $importedOpportunity->getKey(),
                'company_id' => $importedOpportunity->primary_account_id,
                'contract_type_id' => $importedOpportunity->contract_type_id,
                'opportunity_type' => optional($importedOpportunity->contractType)->type_short_name,
                'account_name' => optional($importedOpportunity->primaryAccount)->name,
                'account_manager_name' => optional($importedOpportunity->accountManager)->user_fullname,
                'opportunity_amount' => (float)$importedOpportunity->opportunity_amount,
                'opportunity_start_date' => $importedOpportunity->opportunity_start_date,
                'opportunity_end_date' => $importedOpportunity->opportunity_end_date,
                'opportunity_closing_date' => $importedOpportunity->opportunity_closing_date,
                'sale_action_name' => $importedOpportunity->sale_action_name,
                'project_name' => $importedOpportunity->project_name,
                'created_at' => (string)$importedOpportunity->created_at,
            ]);
        }

        return new BatchOpportunityUploadResult([
            'opportunities' => $imported,
            'errors' => $errors->all()
        ]);
    }

    private function validateBatchOpportunityRow(array $row): Validator
    {
        return $this->validatorFactory->make($row, [
            'primary_account_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'primary_contact_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'nature_of_service' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'ren_month' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'start_date' => [
                'bail', 'nullable', 'date'
            ],
            'ren_year' => [
                'bail', 'nullable', 'integer'
            ],
            'end_date' => [
                'bail', 'nullable', 'date'
            ],
            'customer_status' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'business_partner_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'enduser' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'hw_status' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'region' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'opportunity_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'opportunity_value_foreign_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'opportunity_value_base_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'opportunity_value_currency_code' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'list_price' => [
                'bail', 'nullable', 'numeric'
            ],
            'list_price_foreign_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'list_price_base_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'list_price_currency_code' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'purchase_price' => [
                'bail', 'nullable', 'numeric'
            ],
            'purchase_price_foreign_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'purchase_price_base_value' => [
                'bail', 'nullable', 'numeric'
            ],
            'purchase_price_currency_code' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'ranking' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:1'
            ],
            'personal_rating' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'margin' => [
                'bail', 'nullable', 'numeric'
            ],
            'account_manager' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'closing_date' => [
                'bail', 'required', 'date'
            ],
            'notes' => [
                'bail', 'nullable', 'string', 'max:10000'
            ],
            'sla' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'competition' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'lead_source' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'campaign' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'sales_unit' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'drop_in' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'delayed_closing' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'estimated_upsell_amount' => [
                'bail', 'nullable', 'numeric'
            ],
            'remark' => [
                'bail', 'nullable', 'string', 'max:10000'
            ],
            'higher_sla' => [
                'bail', 'nullable', 'string', 'in:Yes,No'
            ],
            'additional_hardware' => [
                'bail', 'nullable', 'string', 'in:Yes,No'
            ],
            'multi_year' => [
                'bail', 'nullable', 'string', 'in:Yes,No'
            ],
            'service_credits' => [
                'bail', 'nullable', 'string', 'in:Yes,No'
            ],
            'suppliers' => [
                'array'
            ],
            'suppliers.*.country' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'suppliers.*.supplier' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'suppliers.*.contact_name' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'suppliers.*.email_address' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'owner' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'sales_step' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'pipeline' => [
                'bail', 'required', 'string', 'max:191'
            ]
        ]);
    }

    private function mapBatchOpportunityRow(array $row, User $user): CreateOpportunityData
    {
        /** @var Company|null $primaryAccount */
        $primaryAccount = transform($row['primary_account_name'] ?? null, function (string $accountName) {
            $company = Company::query()->where('name', trim($accountName))->where('type', 'External')->first();

            if (is_null($company)) {
                $company = tap(new Company(), function (Company $company) use ($accountName) {
                    $company->name = $accountName;
                    $company->type = 'External';
                    $company->source = 'EQ';
                    $company->category = 'Reseller';
                    $company->save();
                });
            }

            return $company;
        });

        $primaryContact = transform($row['primary_contact_name'] ?? null, function (string $primaryContactName) use ($primaryAccount) {
            if (is_null($primaryAccount)) {
                return null;
            }

            $contact = $primaryAccount->contacts()->where('contact_name', $primaryContactName)->first();

            if (is_null($contact)) {

                $contact = tap(new Contact(), function (Contact $contact) use ($primaryContactName, $primaryAccount) {
                    $contact->contact_type = 'Hardware';
                    $contact->contact_name = $primaryContactName;
                    $contact->first_name = explode(' ', $primaryContactName, 2)[0];

                    if (str_contains($primaryContactName, ' ')) {
                        $contact->last_name = explode(' ', $primaryContactName, 2)[1];
                    }

                    $contact->save();
                });

            }

            $primaryAccount->contacts()->syncWithoutDetaching($contact);

            return $contact;

        });

        $suppliers = with($row['suppliers'] ?? [], function (array $suppliersData) {
            $suppliers = array_map(fn(array $supplier) => [
                'supplier_name' => $supplier['supplier'] ?? null,
                'country_name' => $supplier['country'] ?? null,
                'contact_name' => $supplier['contact_name'] ?? null,
                'contact_email' => $supplier['email_address'] ?? null,
            ], $suppliersData);

            return array_values(array_filter($suppliers, function (array $supplier) {
                return isset($supplier['supplier_name']) ||
                    isset($supplier['country_name']) ||
                    isset($supplier['contact_name']) ||
                    isset($supplier['contact_email']);
            }));
        });

        $contractTypeResolver = static function (?string $opportunityType): ?string {
            if (is_null($opportunityType)) {
                return self::DEFAULT_OPP_TYPE;
            }

            $opportunityType = trim(strtolower($opportunityType));

            return [
                    'pack' => CT_PACK,
                    'contract' => CT_CONTRACT
                ][$opportunityType] ?? self::DEFAULT_OPP_TYPE;
        };

        return new CreateOpportunityData([
            'user_id' => $user->getKey(),
            'contract_type_id' => $contractTypeResolver($row['opportunity_type'] ?? null),
            'account_manager_id' => $this->resolveAccountOwner($row['owner'] ?? null),
            'primary_account_id' => optional($primaryAccount)->getKey(),
            'primary_account_contact_id' => optional($primaryContact)->getKey(),
            'project_name' => $row['business_partner_name'] ?? $row['project_name'] ?? null,
            'nature_of_service' => $row['nature_of_service'] ?? null,
            'renewal_month' => $row['renewal_month'] ?? null,
            'renewal_year' => $row['renewal_year'] ?? null,
            'customer_status' => $row['customer_status'] ?? null,
            'end_user_name' => $row['enduser'] ?? null,
            'hardware_status' => $row['hw_status'] ?? null,
            'region_name' => $row['region'] ?? null,
            'opportunity_start_date' => Carbon::parse($row['start_date']),
            'opportunity_end_date' => Carbon::parse($row['end_date']),
            'opportunity_closing_date' => Carbon::parse($row['closing_date']),

            'opportunity_amount' => transform($row['opportunity_value'] ?? null, fn(string $value) => (float)$value),
            'opportunity_amount_currency_code' => $row['opportunity_value_currency_code'] ?? null,

            'list_price' => transform($row['list_price'] ?? null, fn(string $value) => (float)$value),
            'list_price_currency_code' => $row['list_price_currency_code'] ?? null,

            'purchase_price' => transform($row['purchase_price'] ?? null, fn(string $value) => (float)$value),
            'purchase_price_currency_code' => $row['purchase_price_currency_code'] ?? null,

            'ranking' => transform($row['ranking'] ?? null, fn(string $value) => (float)$value),
            'estimated_upsell_amount' => transform($row['estimated_upsell_amount'] ?? null, fn(string $value) => (float)$value),
            'estimated_upsell_amount_currency_code' => $row['estimated_upsell_amount_currency_code'] ?? null,

            'personal_rating' => $row['personal_rating'] ?? null,

            'margin_value' => transform($row['margin'] ?? null, fn(string $value) => (float)$value),

            'competition_name' => $row['competition'] ?? null,

            'service_level_agreement_id' => $row['sla'] ?? null,
            'sale_unit_name' => $row['sales_unit'] ?? null,
            'drop_in' => $row['drop_in'] ?? null,
            'lead_source_name' => $row['lead_source_name'] ?? null,
            'has_higher_sla' => strtolower($row['higher_sla'] ?? '') === 'yes',
            'is_multi_year' => strtolower($row['multi_year'] ?? '') === 'yes',
            'has_additional_hardware' => strtolower($row['additional_hardware'] ?? '') === 'yes',
            'remarks' => $row['remark'] ?? null,
            'notes' => $row['notes'] ?? null,
            'sale_action_name' => $row['sales_step'] ?? null,

            'create_suppliers' => $suppliers,
        ]);
    }

    private function resolveAccountOwner(?string $accountOwnerName): ?string
    {
        if (is_null($accountOwnerName)) {
            return null;
        }

        return $this->accountOwnerCache[$accountOwnerName] ??= with(trim($accountOwnerName), function (string $accountOwnerName): string {

            $userModelKey = User::query()->where('user_fullname', $accountOwnerName)->value('id');

            if (!is_null($userModelKey)) {
                return $userModelKey;
            }

            $user = tap(new User(), function (User $user) use ($accountOwnerName) {
                if (str_contains($accountOwnerName, ' ')) {
                    [$firstName, $lastName] = explode(' ', $accountOwnerName, 2);
                } else {
                    [$firstName, $lastName] = [$accountOwnerName, ''];
                }

                $user->{$user->getKeyName()} = (string)Uuid::generate(4);
                $user->first_name = $firstName;
                $user->last_name = $lastName;
                $user->email = sprintf('%s@easyquote.com', Str::slug($accountOwnerName, '.'));
                $user->timezone_id = Timezone::query()->where('abbr', 'GMT')->value('id');
                $user->team_id = UT_EPD_WW;

                $user->save();
            });

            return $user->getKey();

        });
    }

    /**
     * @param CreateOpportunityData $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function importOpportunity(CreateOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new Opportunity(), function (Opportunity $opportunity) use ($data) {
            $opportunity->user_id = $data->user_id;
            $opportunity->contract_type_id = $data->contract_type_id;
            $opportunity->project_name = $data->project_name;
            $opportunity->primary_account_id = $data->primary_account_id;
            $opportunity->primary_account_contact_id = $data->primary_account_contact_id;
            $opportunity->account_manager_id = $data->account_manager_id;
            $opportunity->nature_of_service = $data->nature_of_service;
            $opportunity->renewal_month = $data->renewal_month;
            $opportunity->renewal_year = $data->renewal_year;
            $opportunity->customer_status = $data->customer_status;
            $opportunity->end_user_name = $data->end_user_name;
            $opportunity->hardware_status = $data->hardware_status;
            $opportunity->region_name = $data->region_name;
            $opportunity->opportunity_start_date = optional($data->opportunity_start_date)->toDateString();
            $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
            $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
            $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
            $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
            $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
            $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
            $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
            $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->list_price_currency_code = $data->list_price_currency_code;
            $opportunity->personal_rating = $data->personal_rating;
            $opportunity->margin_value = $data->margin_value;
            $opportunity->service_level_agreement_id = $data->service_level_agreement_id;
            $opportunity->sale_unit_name = $data->sale_unit_name;
            $opportunity->drop_in = $data->drop_in;
            $opportunity->lead_source_name = $data->lead_source_name;
            $opportunity->has_higher_sla = $data->has_higher_sla;
            $opportunity->is_multi_year = $data->is_multi_year;
            $opportunity->has_additional_hardware = $data->has_additional_hardware;
            $opportunity->remarks = $data->remarks;
            $opportunity->sale_action_name = $data->sale_action_name;
            $opportunity->ranking = $data->ranking;

            $opportunity->deleted_at = now();

            $this->connection->transaction(function () use ($data, $opportunity) {
                $opportunity->save();

                if (!empty($data->create_suppliers)) {

                    $suppliersData = array_map(fn(CreateSupplierData $supplierData) => [
                        'id' => (string)Uuid::generate(4),
                        'opportunity_id' => $opportunity->getKey(),
                        'supplier_name' => $supplierData->supplier_name,
                        'country_name' => $supplierData->country_name,
                        'contact_name' => $supplierData->contact_name,
                        'contact_email' => $supplierData->contact_email,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ], $data->create_suppliers);

                    OpportunitySupplier::query()->insert($suppliersData);
                }
            });

            $opportunity->setAttribute('account_name', optional($opportunity->primaryAccount)->name);
            $opportunity->setAttribute('account_manager_name', optional($opportunity->accountManager)->user_fullname);
            $opportunity->setAttribute('opportunity_type', optional($opportunity->contractType)->type_short_name);
        });
    }

    /**
     * @param CreateOpportunityData $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function createOpportunity(CreateOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->create_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return tap(new Opportunity(), function (Opportunity $opportunity) use ($data) {
            $opportunity->user_id = $data->user_id;
            $opportunity->contract_type_id = $data->contract_type_id;
            $opportunity->project_name = $data->project_name;
            $opportunity->primary_account_id = $data->primary_account_id;
            $opportunity->primary_account_contact_id = $data->primary_account_contact_id;
            $opportunity->account_manager_id = $data->account_manager_id;
            $opportunity->nature_of_service = $data->nature_of_service;
            $opportunity->renewal_month = $data->renewal_month;
            $opportunity->renewal_year = $data->renewal_year;
            $opportunity->customer_status = $data->customer_status;
            $opportunity->end_user_name = $data->end_user_name;
            $opportunity->hardware_status = $data->hardware_status;
            $opportunity->region_name = $data->region_name;
            $opportunity->opportunity_start_date = optional($data->opportunity_start_date)->toDateString();
            $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
            $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
            $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
            $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
            $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
            $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
            $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
            $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->list_price_currency_code = $data->list_price_currency_code;
            $opportunity->personal_rating = $data->personal_rating;
            $opportunity->margin_value = $data->margin_value;
            $opportunity->service_level_agreement_id = $data->service_level_agreement_id;
            $opportunity->sale_unit_name = $data->sale_unit_name;
            $opportunity->drop_in = $data->drop_in;
            $opportunity->lead_source_name = $data->lead_source_name;
            $opportunity->has_higher_sla = $data->has_higher_sla;
            $opportunity->is_multi_year = $data->is_multi_year;
            $opportunity->has_additional_hardware = $data->has_additional_hardware;
            $opportunity->remarks = $data->remarks;
            $opportunity->sale_action_name = $data->sale_action_name;
            $opportunity->ranking = $data->ranking;

            $this->connection->transaction(function () use ($data, $opportunity) {
                $opportunity->save();

                if (!empty($data->create_suppliers)) {

                    $suppliersData = array_map(fn(CreateSupplierData $supplierData) => [
                        'id' => (string)Uuid::generate(4),
                        'opportunity_id' => $opportunity->getKey(),
                        'supplier_name' => $supplierData->supplier_name,
                        'country_name' => $supplierData->country_name,
                        'contact_name' => $supplierData->contact_name,
                        'contact_email' => $supplierData->contact_email,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ], $data->create_suppliers);

                    OpportunitySupplier::query()->insert($suppliersData);
                }

            });

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($opportunity)
            );

        });
    }

    /**
     * @param Opportunity $opportunity
     * @param UpdateOpportunityData $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function updateOpportunity(Opportunity $opportunity, UpdateOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->create_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        foreach ($data->update_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        return tap($opportunity, function (Opportunity $opportunity) use ($lock, $data) {
            $oldOpportunity = (new Opportunity())->setRawAttributes($opportunity->getRawOriginal());

            $lock->block(30, function () use ($opportunity, $data) {
                $opportunity->contract_type_id = $data->contract_type_id;
                $opportunity->project_name = $data->project_name;
                $opportunity->primary_account_id = $data->primary_account_id;
                $opportunity->primary_account_contact_id = $data->primary_account_contact_id;
                $opportunity->account_manager_id = $data->account_manager_id;
                $opportunity->nature_of_service = $data->nature_of_service;
                $opportunity->renewal_month = $data->renewal_month;
                $opportunity->renewal_year = $data->renewal_year;
                $opportunity->customer_status = $data->customer_status;
                $opportunity->end_user_name = $data->end_user_name;
                $opportunity->hardware_status = $data->hardware_status;
                $opportunity->region_name = $data->region_name;
                $opportunity->opportunity_start_date = optional($data->opportunity_start_date)->toDateString();
                $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
                $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
                $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
                $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
                $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
                $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
                $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
                $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
                $opportunity->opportunity_amount = $data->opportunity_amount;
                $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
                $opportunity->purchase_price = $data->purchase_price;
                $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
                $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
                $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
                $opportunity->list_price = $data->list_price;
                $opportunity->list_price_currency_code = $data->list_price_currency_code;
                $opportunity->personal_rating = $data->personal_rating;
                $opportunity->margin_value = $data->margin_value;
                $opportunity->service_level_agreement_id = $data->service_level_agreement_id;
                $opportunity->sale_unit_name = $data->sale_unit_name;
                $opportunity->drop_in = $data->drop_in;
                $opportunity->lead_source_name = $data->lead_source_name;
                $opportunity->has_higher_sla = $data->has_higher_sla;
                $opportunity->is_multi_year = $data->is_multi_year;
                $opportunity->has_additional_hardware = $data->has_additional_hardware;
                $opportunity->remarks = $data->remarks;
                $opportunity->sale_action_name = $data->sale_action_name;
                $opportunity->ranking = $data->ranking;

                $newOpportunitySuppliers = array_map(function (CreateSupplierData $supplierData) use ($opportunity) {
                    return tap(new OpportunitySupplier(), function (OpportunitySupplier $supplier) use ($opportunity, $supplierData) {
                        $supplier->{$supplier->getKeyName()} = (string)Uuid::generate(4);
                        $supplier->opportunity_id = $opportunity->getKey();
                        $supplier->supplier_name = $supplierData->supplier_name;
                        $supplier->country_name = $supplierData->country_name;
                        $supplier->contact_name = $supplierData->contact_name;
                        $supplier->contact_email = $supplierData->contact_email;
                        $supplier->{$supplier->getCreatedAtColumn()} = $supplier->freshTimestampString();
                        $supplier->{$supplier->getUpdatedAtColumn()} = $supplier->freshTimestampString();
                    });
                }, $data->create_suppliers);

                $batchCreateSupplierData = array_map(fn(OpportunitySupplier $supplier) => $supplier->getAttributes(), $newOpportunitySuppliers);

                $primaryAccount = $opportunity->primaryAccount;
                $newOpportunityAddressModels = [];
                $newOpportunityContactModels = [];
                $newOpportunityAddressPivots = [];
                $newOpportunityContactPivots = [];

                if (!is_null($primaryAccount)) {
                    $alreadyReplicatedAddressKeys = $opportunity
                        ->addresses()
                        ->wherePivotNotNull('replicated_address_id')
                        ->pluck('replicated_address_id')
                        ->all();

                    $alreadyReplicatedContactKeys = $opportunity
                        ->contacts()
                        ->wherePivotNotNull('replicated_contact_id')
                        ->pluck('replicated_contact_id')
                        ->all();

                    $primaryAccount->load(['addresses' => function (Relation $relation) use ($alreadyReplicatedAddressKeys) {
                        $relation->whereKeyNot($alreadyReplicatedAddressKeys);
                    }]);

                    $primaryAccount->load(['contacts' => function (Relation $relation) use ($alreadyReplicatedContactKeys) {
                        $relation->whereKeyNot($alreadyReplicatedContactKeys);
                    }]);

                    [$newOpportunityAddressModels, $newOpportunityAddressPivots] = $this->replicateAddressModelsOfPrimaryAccount($primaryAccount);
                    [$newOpportunityContactModels, $newOpportunityContactPivots] = $this->replicateContactModelsOfPrimaryAccount($primaryAccount);
                }

                $newOpportunityAddressBatch = array_map(fn(Model $model) => $model->getAttributes(), $newOpportunityAddressModels);
                $newOpportunityContactBatch = array_map(fn(Model $model) => $model->getAttributes(), $newOpportunityContactModels);

                $this->connection->transaction(function () use ($newOpportunityContactPivots, $newOpportunityAddressPivots, $newOpportunityContactBatch, $newOpportunityAddressBatch, $batchCreateSupplierData, $data, $opportunity) {
                    $opportunity->save();

                    $existingSupplierKeys = array_map(fn(UpdateSupplierData $supplierData) => $supplierData->supplier_id, $data->update_suppliers);

                    $opportunity->opportunitySuppliers()->whereKeyNot($existingSupplierKeys)->delete();

                    foreach ($data->update_suppliers as $supplierData) {
                        OpportunitySupplier::query()
                            ->whereKey($supplierData->supplier_id)
                            ->update([
                                'supplier_name' => $supplierData->supplier_name,
                                'country_name' => $supplierData->country_name,
                                'contact_name' => $supplierData->contact_name,
                                'contact_email' => $supplierData->contact_email,
                            ]);
                    }

                    OpportunitySupplier::query()->insert($batchCreateSupplierData);

                    if (!empty($newOpportunityAddressBatch)) {
                        Address::query()->insert($newOpportunityAddressBatch);
                    }

                    if (!empty($newOpportunityContactBatch)) {
                        Contact::query()->insert($newOpportunityContactBatch);
                    }

                    if (!empty($newOpportunityAddressPivots)) {
                        $opportunity->addresses()->syncWithoutDetaching($newOpportunityAddressPivots);
                    }

                    if (!empty($newOpportunityContactPivots)) {
                        $opportunity->contacts()->syncWithoutDetaching($newOpportunityContactPivots);
                    }
                });
            });

            $this->eventDispatcher->dispatch(
                new OpportunityUpdated($opportunity, $oldOpportunity),
            );
        });
    }

    private function replicateAddressModelsOfPrimaryAccount(Company $primaryAccount): array
    {
        $newAddressModels = [];
        $newAddressPivots = [];

        foreach ($primaryAccount->addresses as $address) {
            $newAddress = $address->replicate();
            $newAddress->{$newAddress->getKeyName()} = (string)Uuid::generate(4);
            $newAddress->{$newAddress->getCreatedAtColumn()} = $newAddress->freshTimestampString();
            $newAddress->{$newAddress->getUpdatedAtColumn()} = $newAddress->freshTimestampString();

            $newAddressModels[] = $newAddress;
            $newAddressPivots[$newAddress->getKey()] = ['replicated_address_id' => $address->getKey()];
        }

        return [$newAddressModels, $newAddressPivots];
    }

    private function replicateContactModelsOfPrimaryAccount(Company $primaryAccount): array
    {
        $newContactModels = [];
        $newContactPivots = [];

        foreach ($primaryAccount->contacts as $contact) {
            $newContact = $contact->replicate();
            $newContact->{$newContact->getKeyName()} = (string)Uuid::generate(4);
            $newContact->{$newContact->getCreatedAtColumn()} = $newContact->freshTimestampString();
            $newContact->{$newContact->getUpdatedAtColumn()} = $newContact->freshTimestampString();

            $newContactModels[] = $newContact;
            $newContactPivots[$newContact->getKey()] = ['replicated_contact_id' => $contact->getKey()];
        }

        return [$newContactModels, $newContactPivots];
    }


    /**
     * @param Opportunity $opportunity
     */
    public function deleteOpportunity(Opportunity $opportunity): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(fn() => $opportunity->delete());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityDeleted($opportunity)
        );
    }

    /**
     * @param Opportunity $opportunity
     * @param MarkOpportunityAsLostData $data
     */
    public function markOpportunityAsLost(Opportunity $opportunity, MarkOpportunityAsLostData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $opportunity->status = OpportunityStatus::LOST;
        $opportunity->status_reason = $data->status_reason;

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsLost($opportunity)
        );
    }

    public function markOpportunityAsNotLost(Opportunity $opportunity): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $opportunity->status = OpportunityStatus::NOT_LOST;
        $opportunity->status_reason = null;

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsNotLost($opportunity)
        );
    }
}
