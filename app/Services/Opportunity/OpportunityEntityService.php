<?php

namespace App\Services\Opportunity;

use App\Contracts\CauserAware;
use App\Contracts\Services\PermissionBroker;
use App\DTO\{Opportunity\BatchOpportunityUploadResult,
    Opportunity\BatchSaveOpportunitiesData,
    Opportunity\CreateOpportunityData,
    Opportunity\CreateSupplierData,
    Opportunity\ImportedOpportunityData,
    Opportunity\MarkOpportunityAsLostData,
    Opportunity\UpdateOpportunityData,
    Opportunity\UpdateSupplierData,
    Opportunity\UploadOpportunityData
};
use App\Enum\CompanySource;
use App\Enum\CompanyType;
use App\Enum\Lock;
use App\Enum\OpportunityStatus;
use App\Enum\VAT;
use App\Events\{Opportunity\OpportunityBatchFilesImported,
    Opportunity\OpportunityCreated,
    Opportunity\OpportunityDeleted,
    Opportunity\OpportunityMarkedAsLost,
    Opportunity\OpportunityMarkedAsNotLost,
    Opportunity\OpportunityUpdated
};
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\User;
use App\Models\Vendor;
use App\Queries\PipelineQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\ExchangeRate\CurrencyConverter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidatorFactory;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class OpportunityEntityService implements CauserAware
{
    const DEFAULT_PL_ID = PL_WWDP;

    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface   $connection,
                                protected LockProvider          $lockProvider,
                                protected ValidatorInterface    $validator,
                                protected EventDispatcher       $eventDispatcher,
                                protected ValidatorFactory      $validatorFactory,
                                protected CurrencyConverter     $currencyConverter,
                                protected PipelineQueries       $pipelineQueries,
                                protected PermissionBroker      $permissionBroker,
                                protected OpportunityDataMapper $dataMapper)
    {
    }

    public function batchSaveOfImportedOpportunities(BatchSaveOpportunitiesData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->opportunities as $opportunity) {
            $this->finishSavingOfImportedOpportunity($opportunity);
        }
    }

    public function finishSavingOfImportedOpportunity(Opportunity $opportunity): void
    {
        if (false === is_null($opportunity->importedPrimaryAccount)) {
            $primaryAccount = $this->projectImportedCompanyToPrimaryAccount($opportunity->importedPrimaryAccount);

            if ((!$opportunity->importedPrimaryAccount->getFlag(ImportedCompany::IS_RESELLER)
                && !$opportunity->importedPrimaryAccount->getFlag(ImportedCompany::IS_END_USER))
                || $opportunity->importedPrimaryAccount->getFlag(ImportedCompany::IS_RESELLER)
            ) {
                $opportunity->primaryAccount()->associate($primaryAccount);
            }

            if ($opportunity->importedPrimaryAccount->flags === 0 || $opportunity->importedPrimaryAccount->getFlag(ImportedCompany::IS_RESELLER)) {
                $opportunity->primaryAccount()->associate($primaryAccount);
            }

            if ($opportunity->importedPrimaryAccount->getFlag(ImportedCompany::IS_END_USER)) {
                $opportunity->endUser()->associate($primaryAccount);
            }

            if (false === is_null($opportunity->importedPrimaryAccountContact)) {
                $primaryContact = $primaryAccount
                    ->contacts()
                    ->where('first_name', $opportunity->importedPrimaryAccountContact->first_name)
                    ->where('last_name', $opportunity->importedPrimaryAccountContact->last_name)
                    ->first();

                $opportunity->primaryAccountContact()->associate($primaryContact);
            }
        }

        $this->connection->transaction(static fn() => $opportunity->restore());

        $this->eventDispatcher->dispatch(
            new OpportunityCreated($opportunity, $this->causer)
        );
    }

    private function projectImportedCompanyToPrimaryAccount(ImportedCompany $importedCompany): Company
    {
        $matchedCompanies = Company::query()
            ->where('name', trim($importedCompany->company_name))
            ->where('type', CompanyType::EXTERNAL)
            ->with('user:id,email')
            ->get()
            ->sortByDesc(function (Company $company): int {
                if ($company->user?->is($this->causer)) {
                    return 1;
                }

                if ($this->causer instanceof User && $this->causer->checkPermissionTo("companies.*.{$company->getKey()}")) {
                    return 1;
                }

                return 0;
            });

        $company = $matchedCompanies->first();

        /** @var Company $company */
        $company ??= tap(new Company(), function (Company $company) use ($importedCompany) {
            $company->name = $importedCompany->company_name;
            $company->type = CompanyType::EXTERNAL;
            $company->category = $importedCompany->company_category;
            $company->source = CompanySource::PL;
            $company->vat = $importedCompany->vat;
            $company->vat_type = $importedCompany->vat_type ?? VAT::NO_VAT;

            $this->connection->transaction(static fn() => $company->save());

            // once the company has been created by the current authenticated user,
            // we grant super permissions to him on the newly created company.

            if ($this->causer instanceof User) {
                $company->user()->associate($this->causer);

                $this->permissionBroker->givePermissionToUser(
                    user: $this->causer,
                    name: "companies.*.{$company->getKey()}"
                );
            }
        });


        // when the email, phone, or website fields are blank in the company entity,
        // we'll populate their values from the imported company.
        with($company, function (Company $company) use ($importedCompany) {

            if (is_null($company->vat_type)) {
                $company->vat_type = $importedCompany->vat_type ?? VAT::NO_VAT;
            }

            $company->vat = coalesce_blank($company->vat, $importedCompany->vat);
            $company->email = coalesce_blank($company->email, $importedCompany->email);
            $company->phone = coalesce_blank($company->phone, $company->phone, $importedCompany->phone);
            $company->website = coalesce_blank($company->website, $company->website, $importedCompany->website);
        });

        $vendorNames = Str::of($importedCompany->vendors_cs)
            ->explode(',')
            ->map(static fn(string $v) => trim($v))
            ->filter(static fn(string $v) => filled($v))
            ->values()
            ->all();

        $vendors = Vendor::query()
            ->whereIn('name', $vendorNames)
            ->get();

        $this->connection->transaction(static function () use ($vendors, $company): void {
            $company->save();

            $company->vendors()->syncWithoutDetaching($vendors);
        });

        $addressToHash = static function (Address|ImportedAddress $address): string {
            return md5(implode('~', [
                $address->address_type,
                $address->address_1,
                $address->address_2,
                $address->city,
                $address->post_code,
                $address->state,
                $address->country_id,
            ]));
        };

        $contactToHash = static function (Contact|ImportedContact $contact): string {
            return md5(implode('~', [
                $contact->contact_type,
                $contact->first_name,
                $contact->last_name,
                $contact->email,
                $contact->phone,
                $contact->job_title,
            ]));
        };

        $projectImportedAddressToAddress = function (ImportedAddress $importedAddress): Address {
            return tap(new Address(), function (Address $address) use ($importedAddress): void {
                $address->{$address->getKeyName()} = (string)Uuid::generate(4);
                $address->user()->associate($this->causer);
                $address->address_type = $importedAddress->address_type;
                $address->address_1 = $importedAddress->address_1;
                $address->address_2 = $importedAddress->address_2;
                $address->city = $importedAddress->city;
                $address->post_code = $importedAddress->post_code;
                $address->state = $importedAddress->state;
                $address->state_code = $importedAddress->state_code;
                $address->country()->associate($importedAddress->country_id);

                $address->updateTimestamps();
            });
        };

        $projectImportedContactToContact = function (ImportedContact $importedContact): Contact {
            return tap(new Contact(), function (Contact $contact) use ($importedContact): void {
                $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
                $contact->user()->associate($this->causer);
                $contact->contact_type = $importedContact->contact_type;
                $contact->first_name = $importedContact->first_name;
                $contact->last_name = $importedContact->last_name;
                $contact->email = $importedContact->email;
                $contact->phone = $importedContact->phone;
                $contact->job_title = $importedContact->job_title;
                $contact->contact_name = $importedContact->contact_name;

                $contact->updateTimestamps();
            });
        };


        $existingAddressHashes = $company->addresses->keyBy($addressToHash);
        $existingContactHashes = $company->contacts->keyBy($contactToHash);

        $importedAddressHashes = $importedCompany->addresses->keyBy($addressToHash);
        $importedContactHashes = $importedCompany->contacts->keyBy($contactToHash);

        $newImportedAddresses = $importedAddressHashes->diffKeys($existingAddressHashes);
        $newImportedContacts = $importedContactHashes->diffKeys($existingContactHashes);

        $newAddresses = $newImportedAddresses->map($projectImportedAddressToAddress)->values();
        $newContacts = $newImportedContacts->map($projectImportedContactToContact)->values();

        if ($newAddresses->isNotEmpty()) {

            $addressValues = $newAddresses->map->getAttributes()->all();

            $this->connection->transaction(static function () use ($newAddresses, $company, $addressValues): void {
                Address::query()->insert($addressValues);

                $company->addresses()->syncWithoutDetaching($newAddresses);
            });
        }

        if ($newContacts->isNotEmpty()) {

            $contactValues = $newContacts->map->getAttributes()->all();

            $this->connection->transaction(static function () use ($newContacts, $company, $contactValues): void {

                Contact::query()->insert($contactValues);

                $company->contacts()->syncWithoutDetaching($newContacts);

            });

        }

        return $company;
    }

    /**
     * Perform batch Opportunity import.
     *
     * @param \App\DTO\Opportunity\UploadOpportunityData $data
     * @param User $user
     * @return BatchOpportunityUploadResult
     * @throws \App\Services\Exceptions\ValidationException
     * @throws \Throwable
     */
    public function batchImportOpportunities(UploadOpportunityData $data, User $user): BatchOpportunityUploadResult
    {
        $opportunitiesDataFileReader = (new OpportunityBatchFileReader(
            $data->opportunities_file->getRealPath(),
            $data->opportunities_file->getClientOriginalExtension()
        ));

        $opportunitiesFileName = $data->opportunities_file->getClientOriginalName();

        $accountsDataFileReader = null;
        $accountContactsFileReader = null;

        if (!is_null($data->accounts_data_file)) {
            $accountsDataFileReader = AccountsDataBatchFileReader::fromUploadedFile($data->accounts_data_file);
        }

        if (!is_null($data->account_contacts_file)) {
            $accountContactsFileReader = AccountContactBatchFileReader::fromUploadedFile($data->account_contacts_file);
        }

        $accountsDataDictionary = [];
        $accountContactsDictionary = [];

        if (!is_null($accountsDataFileReader)) {
            $accountsDataDictionary = iterator_to_array($accountsDataFileReader->getRows());
        }

        if (!is_null($accountContactsFileReader)) {
            $accountContactsDictionary = value(static function () use ($accountContactsFileReader): array {
                $dictionary = [];

                foreach ($accountContactsFileReader->getRows() as $key => $row) {
                    $dictionary[$key][] = $row;
                }

                return $dictionary;
            });
        }

        $errors = (new MessageBag())
            ->setFormat('Validation failure on :key row. :message');

        $importedOpportunities = [];

        foreach ($opportunitiesDataFileReader->getRows() as $i => $row) {
            $rowFailures = (new OpportunityDataValidator($this->validatorFactory))(
                row: $row,
                accountsDataDictionary: $accountsDataDictionary,
                accountContactsDataDictionary: $accountContactsDictionary
            );

            if ($rowFailures->isNotEmpty()) {
                foreach ($rowFailures->all() as $error) {
                    $errors->add($i + 1, $error);
                }

                continue;
            }

            $importedOpportunities[] = $this->importOpportunity($this->dataMapper->mapImportedOpportunityDataFromImportedRow(
                row: $row,
                accountsDataDictionary: $accountsDataDictionary,
                accountContactsDataDictionary: $accountContactsDictionary,
                user: $user
            ));
        }

        $this->eventDispatcher->dispatch(
            new OpportunityBatchFilesImported(
                opportunitiesDataFile: $data->opportunities_file,
                accountsDataFile: $data->account_contacts_file,
                accountContactsFile: $data->account_contacts_file,
            )
        );

        return new BatchOpportunityUploadResult([
            'opportunities' => $importedOpportunities,
            'errors' => $errors->all("File: '$opportunitiesFileName', Row :key: :message"),
        ]);
    }

    /**
     * @param ImportedOpportunityData $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function importOpportunity(ImportedOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new Opportunity(), function (Opportunity $opportunity) use ($data) {
            $opportunity->pipeline()->associate($data->pipeline_id);
            $opportunity->user()->associate($data->user_id);
            $opportunity->contractType()->associate($data->contract_type_id);
            $opportunity->project_name = $data->project_name;
            $opportunity->importedPrimaryAccount()->associate($data->imported_primary_account_id);
            $opportunity->importedPrimaryAccountContact()->associate($data->imported_primary_account_contact_id);
            $opportunity->accountManager()->associate($data->account_manager_id);
            $opportunity->nature_of_service = $data->nature_of_service;
            $opportunity->renewal_month = $data->renewal_month;
            $opportunity->renewal_year = $data->renewal_year;
            $opportunity->customer_status = $data->customer_status;
            $opportunity->end_user_name = $data->end_user_name;
            $opportunity->hardware_status = $data->hardware_status;
            $opportunity->region_name = $data->region_name;
            $opportunity->opportunity_start_date = $data->opportunity_start_date?->toDateString();
            $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
            $opportunity->opportunity_end_date = $data->opportunity_end_date?->toDateString();
            $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
            $opportunity->opportunity_closing_date = $data->opportunity_closing_date?->toDateString();
            $opportunity->expected_order_date = $data->expected_order_date?->toDateString();
            $opportunity->customer_order_date = $data->customer_order_date?->toDateString();
            $opportunity->purchase_order_date = $data->purchase_order_date?->toDateString();
            $opportunity->supplier_order_date = $data->supplier_order_date?->toDateString();
            $opportunity->supplier_order_transaction_date = $data->supplier_order_transaction_date?->toDateString();
            $opportunity->supplier_order_confirmation_date = $data->supplier_order_confirmation_date?->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->base_opportunity_amount = $data->base_opportunity_amount;
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->base_purchase_price = $data->base_purchase_price;
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->base_list_price = $data->base_list_price;
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
            $opportunity->campaign_name = $data->campaign_name;
            $opportunity->competition_name = $data->competition_name;
            $opportunity->notes = $data->notes;

            $opportunity->{$opportunity->getDeletedAtColumn()} = $opportunity->freshTimestamp();

            $this->connection->transaction(static function () use ($data, $opportunity): void {
                $opportunity->save();

                if (false === empty($data->create_suppliers)) {
                    $suppliersData = array_map(static fn(CreateSupplierData $supplierData): array => [
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

            $opportunity->setAttribute('account_name', $opportunity->importedPrimaryAccount?->company_name);
            $opportunity->setAttribute('account_manager_name', $opportunity->accountManager?->user_fullname);
            $opportunity->setAttribute('opportunity_type', $opportunity->contractType?->type_short_name);
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
            $opportunity->pipeline()->associate($data->pipeline_id);
            $opportunity->user()->associate($data->user_id);
            $opportunity->contractType()->associate($data->contract_type_id);
            $opportunity->project_name = $data->project_name;
            $opportunity->primaryAccount()->associate($data->primary_account_id);
            $opportunity->endUser()->associate($data->end_user_id);
            $opportunity->primaryAccountContact()->associate($data->primary_account_contact_id);
            $opportunity->accountManager()->associate($data->account_manager_id);
            $opportunity->are_end_user_addresses_available = $data->are_end_user_addresses_available;
            $opportunity->are_end_user_contacts_available = $data->are_end_user_contacts_available;
            $opportunity->nature_of_service = $data->nature_of_service;
            $opportunity->renewal_month = $data->renewal_month;
            $opportunity->renewal_year = $data->renewal_year;
            $opportunity->customer_status = $data->customer_status;
            $opportunity->end_user_name = $data->end_user_name;
            $opportunity->hardware_status = $data->hardware_status;
            $opportunity->region_name = $data->region_name;
            $opportunity->opportunity_start_date = $data->opportunity_start_date?->toDateString();
            $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
            $opportunity->opportunity_end_date = $data->opportunity_end_date?->toDateString();
            $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
            $opportunity->opportunity_closing_date = $data->opportunity_closing_date?->toDateString();

            $opportunity->contract_duration_months = $data->contract_duration_months;
            $opportunity->is_contract_duration_checked = $data->is_contract_duration_checked;

            $opportunity->expected_order_date = $data->expected_order_date?->toDateString();
            $opportunity->customer_order_date = $data->customer_order_date?->toDateString();
            $opportunity->purchase_order_date = $data->purchase_order_date?->toDateString();
            $opportunity->supplier_order_date = $data->supplier_order_date?->toDateString();
            $opportunity->supplier_order_transaction_date = $data->supplier_order_transaction_date?->toDateString();
            $opportunity->supplier_order_confirmation_date = $data->supplier_order_confirmation_date?->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->base_opportunity_amount = transform($data->opportunity_amount, function (float $value) use ($data): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->opportunity_amount_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->base_purchase_price = transform($data->purchase_price, function (float $value) use ($data): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->purchase_price_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->base_list_price = transform($data->list_price, function (float $value) use ($data): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->list_price_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
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
            $opportunity->has_service_credits = $data->has_service_credits;
            $opportunity->remarks = $data->remarks;
            $opportunity->sale_action_name = $data->sale_action_name;
            $opportunity->ranking = $data->ranking;
            $opportunity->competition_name = $data->competition_name;
            $opportunity->notes = $data->notes;

            $this->connection->transaction(static function () use ($data, $opportunity): void {
                $opportunity->save();

                if (false === empty($data->create_suppliers)) {
                    $suppliersData = array_map(static fn(CreateSupplierData $supplierData): array => [
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
                new OpportunityCreated($opportunity, $this->causer)
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
                $opportunity->pipeline()->associate($data->pipeline_id);
                $opportunity->contractType()->associate($data->contract_type_id);
                $opportunity->project_name = $data->project_name;
                $opportunity->primaryAccount()->associate($data->primary_account_id);
                $opportunity->endUser()->associate($data->end_user_id);
                $opportunity->primaryAccountContact()->associate($data->primary_account_contact_id);
                $opportunity->accountManager()->associate($data->account_manager_id);
                $opportunity->are_end_user_addresses_available = $data->are_end_user_addresses_available;
                $opportunity->are_end_user_contacts_available = $data->are_end_user_contacts_available;
                $opportunity->nature_of_service = $data->nature_of_service;
                $opportunity->renewal_month = $data->renewal_month;
                $opportunity->renewal_year = $data->renewal_year;
                $opportunity->customer_status = $data->customer_status;
                $opportunity->end_user_name = $data->end_user_name;
                $opportunity->hardware_status = $data->hardware_status;
                $opportunity->region_name = $data->region_name;
                $opportunity->opportunity_start_date = $data->opportunity_start_date?->toDateString();
                $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
                $opportunity->opportunity_end_date = $data->opportunity_end_date?->toDateString();
                $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
                $opportunity->opportunity_closing_date = $data->opportunity_closing_date?->toDateString();

                $opportunity->contract_duration_months = $data->contract_duration_months;
                $opportunity->is_contract_duration_checked = $data->is_contract_duration_checked;

                $opportunity->expected_order_date = $data->expected_order_date?->toDateString();
                $opportunity->customer_order_date = $data->customer_order_date?->toDateString();
                $opportunity->purchase_order_date = $data->purchase_order_date?->toDateString();
                $opportunity->supplier_order_date = $data->supplier_order_date?->toDateString();
                $opportunity->supplier_order_transaction_date = $data->supplier_order_transaction_date?->toDateString();
                $opportunity->supplier_order_confirmation_date = $data->supplier_order_confirmation_date?->toDateString();
                $opportunity->opportunity_amount = $data->opportunity_amount;
                $opportunity->base_opportunity_amount = transform($data->opportunity_amount, function (float $value) use ($data): float {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        fromCode: $data->opportunity_amount_currency_code ?? $baseCurrency,
                        toCode: $baseCurrency,
                        amount: $value
                    );
                });
                $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
                $opportunity->purchase_price = $data->purchase_price;
                $opportunity->base_purchase_price = transform($data->purchase_price, function (float $value) use ($data): float {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        fromCode: $data->purchase_price_currency_code ?? $baseCurrency,
                        toCode: $baseCurrency,
                        amount: $value
                    );
                });
                $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
                $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
                $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
                $opportunity->list_price = $data->list_price;
                $opportunity->base_list_price = transform($data->list_price, function (float $value) use ($data): float {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        fromCode: $data->list_price_currency_code ?? $baseCurrency,
                        toCode: $baseCurrency,
                        amount: $value
                    );
                });
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
                $opportunity->has_service_credits = $data->has_service_credits;
                $opportunity->remarks = $data->remarks;
                $opportunity->sale_action_name = $data->sale_action_name;
                $opportunity->ranking = $data->ranking;
                $opportunity->campaign_name = $data->campaign_name;
                $opportunity->competition_name = $data->competition_name;
                $opportunity->notes = $data->notes;

                $newOpportunitySuppliers = array_map(static function (CreateSupplierData $supplierData) use ($opportunity): OpportunitySupplier {
                    return tap(new OpportunitySupplier(), static function (OpportunitySupplier $supplier) use ($opportunity, $supplierData): void {
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

                $batchCreateSupplierData = array_map(static fn(OpportunitySupplier $supplier): array => $supplier->getAttributes(), $newOpportunitySuppliers);

                $this->connection->transaction(static function () use ($batchCreateSupplierData, $data, $opportunity): void {
                    $opportunity->save();

                    $existingSupplierKeys = array_map(static fn(UpdateSupplierData $supplierData) => $supplierData->supplier_id, $data->update_suppliers);

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
                });
            });

            $this->eventDispatcher->dispatch(
                new OpportunityUpdated($opportunity, $oldOpportunity, $this->causer),
            );
        });
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

            $this->connection->transaction(static fn() => $opportunity->delete());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityDeleted($opportunity, $this->causer)
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

            $this->connection->transaction(static fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsLost($opportunity, $this->causer)
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

            $this->connection->transaction(static fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsNotLost($opportunity, $this->causer)
        );
    }

    public function syncPrimaryAccountContacts(Company $primaryAccount): void
    {
        $opportunityModel = (new Opportunity());

        // Set primary account contact to null,
        // where primary account contact was detached from the corresponding primary account.
        $opportunityModel->newQuery()
            ->where($opportunityModel->primaryAccount()->getQualifiedForeignKeyName(), $primaryAccount->getKey())
            ->whereNotNull($opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
            ->whereNotExists(static function (BaseBuilder $builder) use ($opportunityModel, $primaryAccount) {
                $builder->selectRaw(1)
                    ->from($primaryAccount->contacts()->getTable())
                    ->whereColumn($primaryAccount->contacts()->getQualifiedRelatedPivotKeyName(), $opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
                    ->where($primaryAccount->contacts()->getQualifiedForeignPivotKeyName(), $primaryAccount->getKey());
            })
            ->update([$opportunityModel->primaryAccountContact()->getForeignKeyName() => null]);
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
