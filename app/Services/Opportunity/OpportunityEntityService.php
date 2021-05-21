<?php

namespace App\Services\Opportunity;

use App\Queries\PipelineQueries;
use App\DTO\{Opportunity\BatchOpportunityUploadResult,
    Opportunity\BatchSaveOpportunitiesData,
    Opportunity\CreateOpportunityData,
    Opportunity\CreateSupplierData,
    Opportunity\ImportedOpportunityData,
    Opportunity\ImportOpportunityData,
    Opportunity\MarkOpportunityAsLostData,
    Opportunity\UpdateOpportunityData,
    Opportunity\UpdateSupplierData};
use App\Enum\AccountCategory;
use App\Enum\Lock;
use App\Enum\OpportunityStatus;
use App\Enum\VAT;
use App\Events\{Opportunity\OpportunityBatchFilesImported,
    Opportunity\OpportunityCreated,
    Opportunity\OpportunityDeleted,
    Opportunity\OpportunityMarkedAsLost,
    Opportunity\OpportunityMarkedAsNotLost,
    Opportunity\OpportunityUpdated};
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Timezone;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\User;
use App\Services\Exceptions\ValidationException;
use App\Services\ExchangeRate\CurrencyConverter;
use App\Services\Opportunity\Models\PipelinerOppMap;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidatorFactory;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class OpportunityEntityService
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    const DEFAULT_PL_ID = PL_WWDP;

    protected ConnectionInterface $connection;
    protected LockProvider $lockProvider;
    protected ValidatorInterface $validator;
    protected EventDispatcher $eventDispatcher;
    protected ValidatorFactory $validatorFactory;
    protected CurrencyConverter $currencyConverter;
    protected PipelineQueries $pipelineQueries;

    private array $accountOwnerCache = [];
    private array $countryNameOfSupplierCache = [];

    public function __construct(ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                ValidatorInterface $validator,
                                EventDispatcher $eventDispatcher,
                                ValidatorFactory $validatorFactory,
                                CurrencyConverter $currencyConverter,
    PipelineQueries $pipelineQueries)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->validatorFactory = $validatorFactory;
        $this->currencyConverter = $currencyConverter;
        $this->pipelineQueries = $pipelineQueries;
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
     * @param \App\DTO\Opportunity\ImportOpportunityData $data
     * @param User $user
     * @return BatchOpportunityUploadResult
     * @throws \App\Services\Exceptions\ValidationException
     * @throws \Throwable
     */
    public function batchImportOpportunities(ImportOpportunityData $data, User $user): BatchOpportunityUploadResult
    {
        $opportunitiesDataFileReader = (new OpportunityBatchFileReader(
            $data->opportunities_file->getRealPath(),
            $data->opportunities_file->getClientOriginalExtension()
        ));

        $opportunitiesFileName = $data->opportunities_file->getClientOriginalName();

        $accountsDataFileReader = null;
        $accountContactsFileReader = null;

        if (!is_null($data->accounts_data_file)) {
            $accountsDataFileReader = (new AccountsDataBatchFileReader(
                $data->accounts_data_file->getRealPath(),
                $data->accounts_data_file->getClientOriginalExtension()
            ));
        }

        if (!is_null($data->account_contacts_file)) {
            $accountContactsFileReader = (new AccountContactBatchFileReader(
                $data->account_contacts_file->getRealPath(),
                $data->account_contacts_file->getClientOriginalExtension()
            ));
        }

        $accountsDataDictionary = [];
        $accountContactsDictionary = [];

        if (!is_null($accountsDataFileReader)) {
            $accountsDataDictionary = iterator_to_array($accountsDataFileReader->getRows());
        }

        if (!is_null($accountContactsFileReader)) {
            $accountContactsDictionary = value(function () use ($accountContactsFileReader): array {
                $dictionary = [];

                foreach ($accountContactsFileReader->getRows() as $key => $row) {
                    $dictionary[$key][] = $row;
                }

                return $dictionary;
            });
        }

        $errors = (new MessageBag())
            ->setFormat('Validation failure on :key row. :message');

        $imported = [];

        static $baseCurrencySymbol;

        if (!isset($baseCurrencySymbol)) {
            $baseCurrencySymbol = Currencies::getSymbol('GBP');
        }

        foreach ($opportunitiesDataFileReader->getRows() as $i => $row) {
            $rowFailures = $this->validateBatchOpportunityRow(
                $row,
                $accountsDataDictionary,
                $accountContactsDictionary
            );

            if ($rowFailures->isNotEmpty()) {
                foreach ($rowFailures->all() as $error) {
                    $errors->add($i + 1, $error);
                }

                continue;
            }

            $importedOpportunity = $this->importOpportunity($this->mapBatchOpportunityRow(
                $row,
                $accountsDataDictionary,
                $accountContactsDictionary,
                $user
            ));

            $imported[] = new ImportedOpportunityData([
                'id' => $importedOpportunity->getKey(),
                'company_id' => $importedOpportunity->primary_account_id,
                'contract_type_id' => $importedOpportunity->contract_type_id,
                'opportunity_type' => optional($importedOpportunity->contractType)->type_short_name,
                'account_name' => optional($importedOpportunity->primaryAccount)->name,
                'account_manager_name' => optional($importedOpportunity->accountManager)->user_fullname,
                'opportunity_amount' => (float)$importedOpportunity->base_opportunity_amount,
                'opportunity_amount_formatted' => sprintf('%s %s', $baseCurrencySymbol, number_format((float)$importedOpportunity->base_opportunity_amount, 2)),
                'opportunity_start_date' => $importedOpportunity->opportunity_start_date,
                'opportunity_end_date' => $importedOpportunity->opportunity_end_date,
                'opportunity_closing_date' => $importedOpportunity->opportunity_closing_date,
                'sale_action_name' => $importedOpportunity->sale_action_name,
                'project_name' => $importedOpportunity->project_name,
                'campaign_name' => $importedOpportunity->campaign_name,
                'created_at' => (string)$importedOpportunity->created_at,
            ]);
        }

        $this->eventDispatcher->dispatch(
            new OpportunityBatchFilesImported(
                $data->opportunities_file,
                $data->account_contacts_file,
                $data->account_contacts_file,
            )
        );

        return new BatchOpportunityUploadResult([
            'opportunities' => $imported,
            'errors' => $errors->all("File: '$opportunitiesFileName', Row :key: :message")
        ]);
    }

    private function validateBatchOpportunityRow(array $row, array $accountsDataDictionary, array $accountContactsDataDictionary): MessageBag
    {
        return tap(new MessageBag(), function (MessageBag $errors) use ($row, $accountsDataDictionary, $accountContactsDataDictionary) {
            $validator = $this->validatorFactory->make($row, [
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

            $errors->merge($validator->errors());

            $accountName = $row['primary_account_name'] ?? null;

            if (is_null($accountName)) {
                return;
            }

            $accountNameHash = md5($accountName);

            $accountExists = Company::query()->where('name', trim($accountName))->where('type', 'External')->exists();

            $accountData = $accountsDataDictionary[$accountNameHash] ?? null;
            $accountContactData = $accountContactsDataDictionary[$accountNameHash] ?? null;

            if ($accountExists === true) {
                return;
            }

            if (is_null($accountData)) {
                $errors->add('primary_account_data', "No data provided for a new Primary Account, Name: '$accountName'.");
            }
        });

    }

    private function mapPrimaryAccountData(string $accountName, array $accountsDataDictionary, array $accountContactsDataDictionary): Company
    {
        $accountNameHash = md5($accountName);

        $accountData = $accountsDataDictionary[$accountNameHash] ?? null;
        $accountContactData = $accountContactsDataDictionary[$accountNameHash] ?? null;

        $company = Company::query()->where('name', trim($accountName))->where('type', 'External')->first();

        $categoryOfCompanyResolver = static function (?array $accountData) {
            static $categoryDictionary = [
                'distributor' => AccountCategory::RESELLER,
                'business_partner' => AccountCategory::BUSINESS_PARTNER,
                'reseller' => AccountCategory::RESELLER,
                'end_user' => AccountCategory::END_USER
            ];

            if (is_null($accountData)) {
                return 'Reseller';
            }

            $categoryDictionaryOfAccountData = Arr::only($accountData, ['distributor', 'business_partner', 'reseller', 'end_user']);

            foreach ($categoryDictionaryOfAccountData as $key => $value) {
                if (strtolower(trim($value)) === 'yes') {
                    return $categoryDictionary[$key];
                }

            }

            return AccountCategory::RESELLER;
        };

        if (is_null($company)) {
            $company = tap(new Company(), function (Company $company) use ($accountData, $categoryOfCompanyResolver, $accountName) {
                $company->{$company->getKeyName()} = (string)Uuid::generate(4);
                $company->name = $accountName;
                $company->type = 'External';
                $company->source = 'EQ';
                $company->vat_type = VAT::NO_VAT;
                $company->category = $categoryOfCompanyResolver($accountData);
            });
        }

        /** @var Company $company */

        if (!is_null($accountData)) {
            $company->email ??= $accountData['primary_e_mail'] ?? $company->email;
            $company->phone ??= $accountData['primary_phone'] ?? $company->phone;
            $company->website ??= $accountData['home_page'] ?? $company->website;
        }

        $company->save();

        if (is_null($accountContactData)) {
            return $company;
        }

        $newAddressDataOfCompany = [];
        $newContactDataOfCompany = [];

        foreach ($accountContactData as $contactData) {
            $newAddressDataOfCompany[] = tap(new Address(), function (Address $address) use ($contactData) {
                $address->{$address->getKeyName()} = (string)Uuid::generate(4);
                $address->address_type = 'Invoice';

                [$addressOne, $addressTwo] = transform($contactData['street_address'] ?? null, function (string $streetAddress) {
                    if (str_contains($streetAddress, "\n")) {
                        return explode("\n", $streetAddress);
                    }

                    return [$streetAddress, null];
                }, [null, null]);

                $address->address_1 = transform($addressOne, fn(string $address) => trim($address, " \t\n\r\0\x0B,"));
                $address->address_2 = transform($addressTwo, fn(string $address) => trim($address, " \t\n\r\0\x0B,"));

                $address->city = $contactData['city'] ?? null;
                $address->contact_name = $contactData['owner'] ?? null;
                $address->contact_number = $contactData['primary_phone'] ?? null;
                $address->contact_email = $contactData['primary_e_mail'] ?? null;
                $address->post_code = $contactData['zip_code'] ?? null;
                $address->state = $contactData['state_province'] ?? null;

                if (isset($contactData['country'])) {
                    $address->country()->associate(
                        Country::query()->where('name', $contactData['country'])->first()
                    );
                }

                $address->{$address->getCreatedAtColumn()} = $address->freshTimestampString();
                $address->{$address->getUpdatedAtColumn()} = $address->freshTimestampString();
            });

            $newContactDataOfCompany[] = tap(new Contact(), function (Contact $contact) use ($contactData) {
                $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
                $contact->first_name = $contactData['first_name'] ?? null;
                $contact->last_name = $contactData['last_name'] ?? null;
                $contact->email = $contactData['primary_e_mail'] ?? null;
                $contact->phone = $contactData['primary_phone'] ?? null;
                $contact->job_title = $contactData['titlesalutation'] ?? null;
                $contact->is_verified = false;

                $contact->{$contact->getCreatedAtColumn()} = $contact->freshTimestampString();
                $contact->{$contact->getUpdatedAtColumn()} = $contact->freshTimestampString();
            });
        }

        $company->load(['addresses', 'contacts']);

        $addressToHash = static function (Address $address): string {
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

        $contactToHash = static function (Contact $contact): string {
            return md5(implode('~', [
                $contact->first_name,
                $contact->last_name,
                $contact->email,
                $contact->phone,
                $contact->job_title,
            ]));
        };

        $existingAddressHashes = array_flip(array_map($addressToHash, $company->addresses->all()));
        $existingContactHashes = array_flip(array_map($contactToHash, $company->contacts->all()));

        $newAddressDataOfCompany = array_values(array_filter($newAddressDataOfCompany, function (Address $address) use ($existingAddressHashes, $addressToHash) {
            $addressHash = $addressToHash($address);

            return !isset($existingAddressHashes[$addressHash]);
        }));

        $newContactDataOfCompany = array_values(array_filter($newContactDataOfCompany, function (Contact $contact) use ($existingContactHashes, $contactToHash) {
            $contactHash = $contactToHash($contact);

            return !isset($existingContactHashes[$contactHash]);
        }));

        $newAddressDataOfCompanyDictionary = [];
        $newContactDataOfCompanyDictionary = [];

        foreach ($newAddressDataOfCompany as $address) {
            $newAddressDataOfCompanyDictionary[$addressToHash($address)] = $address;
        }

        foreach ($newContactDataOfCompany as $contact) {
            $newContactDataOfCompanyDictionary[$contactToHash($contact)] = $contact;
        }

        $newBatchAddressDataOfCompany = array_map(fn(Model $model) => $model->getAttributes(), array_values($newAddressDataOfCompanyDictionary));
        $newBatchContactDataOfCompany = array_map(fn(Model $model) => $model->getAttributes(), array_values($newContactDataOfCompanyDictionary));

        if (!empty($newBatchAddressDataOfCompany)) {
            Address::query()->insert($newBatchAddressDataOfCompany);

            $company->addresses()->syncWithoutDetaching(array_column($newBatchAddressDataOfCompany, 'id'));
        }

        if (!empty($newContactDataOfCompany)) {
            Contact::query()->insert($newBatchContactDataOfCompany);

            $company->contacts()->syncWithoutDetaching(array_column($newBatchContactDataOfCompany, 'id'));
        }

        return $company;
    }

    private function mapBatchOpportunityRow(array $row, array $accountsDataDictionary, array $accountContactsDataDictionary, User $user): CreateOpportunityData
    {
        /** @var Company|null $primaryAccount */
        $primaryAccount = transform($row['primary_account_name'] ?? null, function (string $accountName) use ($accountContactsDataDictionary, $accountsDataDictionary) {
            return $this->mapPrimaryAccountData(
                $accountName,
                $accountsDataDictionary,
                $accountContactsDataDictionary
            );
        });

        $primaryContact = transform($row['primary_contact_name'] ?? null, function (string $primaryContactName) use ($primaryAccount) {
            if (is_null($primaryAccount)) {
                return null;
            }

            $primaryContactName = trim($primaryContactName);

            [$contactFirstName, $contactLastName] = value(function () use ($primaryContactName): array {

                if (str_contains($primaryContactName, ' ')) {
                    return explode(' ', $primaryContactName, 2);
                }

                return [$primaryContactName, null];
            });

            $contact = $primaryAccount->contacts()
                ->where('first_name', $contactFirstName)
                ->where('last_name', $contactLastName)
                ->first();

            if (is_null($contact)) {

                $contact = tap(new Contact(), function (Contact $contact) use ($contactLastName, $contactFirstName, $primaryContactName, $primaryAccount) {
                    $contact->contact_type = 'Hardware';
                    $contact->contact_name = $primaryContactName;
                    $contact->first_name = $contactFirstName;
                    $contact->last_name = $contactLastName;
                    $contact->is_verified = true;

                    $contact->save();
                });

            }

            $primaryAccount->contacts()->syncWithoutDetaching($contact);

            return $contact;

        });

        $valueRetriever = function (array $row, array $keys, $default = null) {
            foreach ($keys as $key) {
                if (isset($row[$key])) {
                    return $row[$key];
                }
            }

            return $default;
        };

        $suppliers = with($valueRetriever($row, PipelinerOppMap::SUPPLIERS, []), function (array $suppliersData) {
            $suppliers = array_map(fn(array $supplier) => [
                'supplier_name' => $supplier['supplier'] ?? null,
                'country_name' => $this->normalizeCountryNameOfSupplier($supplier['country'] ?? null),
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
            'pipeline_id' => value(function () {
                return $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole()->getKey();
            }),
            'user_id' => $user->getKey(),
            'contract_type_id' => $contractTypeResolver($valueRetriever($row, PipelinerOppMap::CONTRACT_TYPE)),
            'account_manager_id' => $this->resolveAccountOwner($valueRetriever($row, PipelinerOppMap::ACCOUNT_MANAGER)),
            'primary_account_id' => optional($primaryAccount)->getKey(),
            'primary_account_contact_id' => optional($primaryContact)->getKey(),
            'project_name' => $valueRetriever($row, PipelinerOppMap::PROJECT_NAME),
            'nature_of_service' => $valueRetriever($row, PipelinerOppMap::NATURE_OF_SERVICE),
            'renewal_month' => $valueRetriever($row, PipelinerOppMap::RENEWAL_MONTH),
            'renewal_year' => transform($valueRetriever($row, PipelinerOppMap::RENEWAL_YEAR), fn(string $value) => (int)$value),
            'customer_status' => $valueRetriever($row, PipelinerOppMap::CUSTOMER_STATUS),
            'end_user_name' => $valueRetriever($row, PipelinerOppMap::END_USER_NAME),
            'hardware_status' => $valueRetriever($row, PipelinerOppMap::HARDWARE_STATUS),
            'region_name' => $valueRetriever($row, PipelinerOppMap::REGION_NAME),
            'opportunity_start_date' => transform($valueRetriever($row, PipelinerOppMap::OPPORTUNITY_START_DATE), fn($date) => Carbon::parse($date)),
            'is_opportunity_start_date_assumed' => strtolower($valueRetriever($row, PipelinerOppMap::IS_OPPORTUNITY_START_DATE_ASSUMED)) === 'yes',
            'opportunity_end_date' => transform($valueRetriever($row, PipelinerOppMap::OPPORTUNITY_END_DATE), fn($date) => Carbon::parse($date)),
            'is_opportunity_end_date_assumed' => strtolower($valueRetriever($row, PipelinerOppMap::IS_OPPORTUNITY_END_DATE_ASSUMED)) === 'yes',
            'opportunity_closing_date' => transform($valueRetriever($row, PipelinerOppMap::OPPORTUNITY_CLOSING_DATE), fn($date) => Carbon::parse($date)),

            'base_opportunity_amount' => transform($valueRetriever($row, PipelinerOppMap::BASE_OPPORTUNITY_AMOUNT), fn(string $value) => (float)$value),
            'opportunity_amount' => transform($valueRetriever($row, PipelinerOppMap::OPPORTUNITY_AMOUNT), fn(string $value) => (float)$value),
            'opportunity_amount_currency_code' => $valueRetriever($row, PipelinerOppMap::OPPORTUNITY_AMOUNT_CURRENCY_CODE),

            'base_list_price' => transform($valueRetriever($row, PipelinerOppMap::BASE_LIST_PRICE), fn(string $value) => (float)$value),
            'list_price' => transform($valueRetriever($row, PipelinerOppMap::LIST_PRICE), fn(string $value) => (float)$value),
            'list_price_currency_code' => $valueRetriever($row, PipelinerOppMap::LIST_PRICE_CURRENCY_CODE),

            'base_purchase_price' => transform($valueRetriever($row, PipelinerOppMap::BASE_PURCHASE_PRICE), fn(string $value) => (float)$value),
            'purchase_price' => transform($valueRetriever($row, PipelinerOppMap::PURCHASE_PRICE), fn(string $value) => (float)$value),
            'purchase_price_currency_code' => $valueRetriever($row, PipelinerOppMap::PURCHASE_PRICE_CURRENCY_CODE),

            'ranking' => transform($valueRetriever($row, PipelinerOppMap::RANKING), fn(string $value) => (float)$value),
            'estimated_upsell_amount' => transform($valueRetriever($row, PipelinerOppMap::ESTIMATED_UPSELL_AMOUNT), fn(string $value) => (float)$value),
            'estimated_upsell_amount_currency_code' => $valueRetriever($row, PipelinerOppMap::ESTIMATED_UPSELL_AMOUNT_CURRENCY_CODE),

            'personal_rating' => $valueRetriever($row, PipelinerOppMap::PERSONAL_RATING),

            'margin_value' => transform($valueRetriever($row, PipelinerOppMap::MARGIN_VALUE), fn(string $value) => (float)$value),

            'competition_name' => $valueRetriever($row, PipelinerOppMap::COMPETITION_NAME),

            'service_level_agreement_id' => $valueRetriever($row, PipelinerOppMap::SERVICE_LEVEL_AGREEMENT_ID),
            'sale_unit_name' => $valueRetriever($row, PipelinerOppMap::SALE_UNIT_NAME),
            'drop_in' => $valueRetriever($row, PipelinerOppMap::DROP_IN),
            'lead_source_name' => $valueRetriever($row, PipelinerOppMap::LEAD_SOURCE_NAME),
            'has_higher_sla' => strtolower($valueRetriever($row, PipelinerOppMap::HAS_HIGHER_SLA, '')) === 'yes',
            'is_multi_year' => strtolower($valueRetriever($row, PipelinerOppMap::IS_MULTI_YEAR, '')) === 'yes',
            'has_additional_hardware' => strtolower($valueRetriever($row, PipelinerOppMap::HAS_ADDITIONAL_HARDWARE, '')) === 'yes',
            'remarks' => $valueRetriever($row, PipelinerOppMap::REMARKS),
            'notes' => $valueRetriever($row, PipelinerOppMap::NOTES),
            'sale_action_name' => $valueRetriever($row, PipelinerOppMap::SALE_ACTION_NAME),

            'campaign_name' => $valueRetriever($row, PipelinerOppMap::CAMPAIGN_NAME),

            'create_suppliers' => $suppliers,
        ]);
    }

    private function normalizeCountryNameOfSupplier(?string $countryName): ?string
    {
        if (is_null($countryName) || trim($countryName) === '') {
            return null;
        }

        return $this->countryNameOfSupplierCache[$countryName] ??= with(trim($countryName), function (string $countryName) {

            $normalizedCountryName = [
                    'UK' => 'GB'
                ][$countryName] ?? $countryName;

            return Country::query()
                ->where('iso_3166_2', $normalizedCountryName)
                ->orWhere('name', $normalizedCountryName)
                ->value('name');

        });
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
            $opportunity->pipeline()->associate($data->pipeline_id);
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
            $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
            $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
            $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
            $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
            $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
            $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
            $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
            $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
            $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
            $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
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
            $opportunity->pipeline()->associate($data->pipeline_id);
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
            $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
            $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
            $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
            $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
            $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
            $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
            $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
            $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
            $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
            $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->base_opportunity_amount = transform($data->opportunity_amount, function (float $value) use ($data) {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    $data->opportunity_amount_currency_code ?? $baseCurrency,
                    $baseCurrency,
                    $value
                );
            });
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->base_purchase_price = transform($data->purchase_price, function (float $value) use ($data) {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    $data->purchase_price_currency_code ?? $baseCurrency,
                    $baseCurrency,
                    $value
                );
            });
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->base_list_price = transform($data->list_price, function (float $value) use ($data) {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    $data->list_price_currency_code ?? $baseCurrency,
                    $baseCurrency,
                    $value
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
                $opportunity->pipeline()->associate($data->pipeline_id);
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
                $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
                $opportunity->opportunity_end_date = optional($data->opportunity_end_date)->toDateString();
                $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
                $opportunity->opportunity_closing_date = optional($data->opportunity_closing_date)->toDateString();
                $opportunity->expected_order_date = optional($data->expected_order_date)->toDateString();
                $opportunity->customer_order_date = optional($data->customer_order_date)->toDateString();
                $opportunity->purchase_order_date = optional($data->purchase_order_date)->toDateString();
                $opportunity->supplier_order_date = optional($data->supplier_order_date)->toDateString();
                $opportunity->supplier_order_transaction_date = optional($data->supplier_order_transaction_date)->toDateString();
                $opportunity->supplier_order_confirmation_date = optional($data->supplier_order_confirmation_date)->toDateString();
                $opportunity->opportunity_amount = $data->opportunity_amount;
                $opportunity->base_opportunity_amount = transform($data->opportunity_amount, function (float $value) use ($data) {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        $data->opportunity_amount_currency_code ?? $baseCurrency,
                        $baseCurrency,
                        $value
                    );
                });
                $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
                $opportunity->purchase_price = $data->purchase_price;
                $opportunity->base_purchase_price = transform($data->purchase_price, function (float $value) use ($data) {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        $data->purchase_price_currency_code ?? $baseCurrency,
                        $baseCurrency,
                        $value
                    );
                });
                $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
                $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
                $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
                $opportunity->list_price = $data->list_price;
                $opportunity->base_list_price = transform($data->list_price, function (float $value) use ($data) {
                    $baseCurrency = $this->currencyConverter->getBaseCurrency();

                    return $this->currencyConverter->convertCurrencies(
                        $data->list_price_currency_code ?? $baseCurrency,
                        $baseCurrency,
                        $value
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
                $opportunity->remarks = $data->remarks;
                $opportunity->sale_action_name = $data->sale_action_name;
                $opportunity->ranking = $data->ranking;
                $opportunity->campaign_name = $data->campaign_name;

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

                $this->connection->transaction(function () use ($batchCreateSupplierData, $data, $opportunity) {
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
                });
            });

            $this->eventDispatcher->dispatch(
                new OpportunityUpdated($opportunity, $oldOpportunity),
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

    public function syncPrimaryAccountContacts(Company $primaryAccount): void
    {
        $opportunityModel = (new Opportunity());

        // Set primary account contact to null,
        // where primary account contact was detached from the corresponding primary account.
        $opportunityModel->newQuery()
            ->where($opportunityModel->primaryAccount()->getQualifiedForeignKeyName(), $primaryAccount->getKey())
            ->whereNotNull($opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
            ->whereNotExists(function (BaseBuilder $builder) use ($opportunityModel, $primaryAccount) {
                $builder->selectRaw(1)
                    ->from($primaryAccount->contacts()->getTable())
                    ->whereColumn($primaryAccount->contacts()->getQualifiedRelatedPivotKeyName(), $opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
                    ->where($primaryAccount->contacts()->getQualifiedForeignPivotKeyName(), $primaryAccount->getKey());
            })
//            ->get()
            ->update([$opportunityModel->primaryAccountContact()->getForeignKeyName() => null]);
    }
}
