<?php

namespace App\Services\Opportunity;

use App\Contracts\CauserAware;
use App\DTO\Opportunity\ImportedOpportunityData;
use App\Enum\AccountCategory;
use App\Enum\AddressType;
use App\Enum\ContactType;
use App\Enum\VAT;
use App\Models\Data\Country;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\Pipeline\Pipeline;
use App\Models\User;
use App\Queries\PipelineQueries;
use App\Services\Opportunity\Models\PipelinerOppMap;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Webpatser\Uuid\Uuid;

class OpportunityDataMapper implements CauserAware
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    protected readonly ?Pipeline $defaultPipeline;

    protected array $countryNameOfSupplierCache = [];

    protected ?Model $causer;

    public function __construct(protected PipelineQueries      $pipelineQueries,
                                protected AccountOwnerResolver $accountOwnerResolver,
                                protected ContractTypeResolver $contractTypeResolver)
    {
    }

    public function mapSuppliers(array $row): array
    {
        $suppliersData = (array)self::coalesceMap($row, PipelinerOppMap::SUPPLIERS, []);

        return collect($suppliersData)
            ->map(fn(array $supplier): array => [
                'supplier_name' => $supplier['supplier'] ?? null,
                'country_name' => $this->normalizeCountryNameOfSupplier($supplier['country'] ?? null),
                'contact_name' => $supplier['contact_name'] ?? null,
                'contact_email' => $supplier['email_address'] ?? null,
            ])
            // filter the suppliers with any filled field
            ->filter(static function (array $supplier): bool {
                return collect($supplier)->contains(static fn(mixed $v): bool => filled($v));
            })
            ->values()
            ->all();
    }

    private function normalizeCountryNameOfSupplier(?string $countryName): ?string
    {
        if (blank($countryName)) {
            return null;
        }

        $countryName = trim($countryName);

        if (key_exists($countryName, $this->countryNameOfSupplierCache)) {
            return $this->countryNameOfSupplierCache[$countryName];
        }

        $normalizedCountryName = match ($countryName) {
            'UK' => 'GB',
            default => $countryName,
        };

        return $this->countryNameOfSupplierCache[$countryName] = Country::query()
            ->where('iso_3166_2', $normalizedCountryName)
            ->orWhere('name', $normalizedCountryName)
            ->value('name');
    }

    public static function coalesceMap(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (isset($row[$key])) {
                return $row[$key];
            }
        }

        return $default;
    }

    public function mapImportedCompany(string $accountName,
                                       string $primaryContactName,
                                       array  $accountData,
                                       array  $contacts): ImportedCompany
    {
        return tap(new ImportedCompany(), function (ImportedCompany $account) use ($primaryContactName, $contacts, $accountData, $accountName): void {
            $account->company_name = $accountName;
            $account->company_category = self::resolveCategoryOfCompany($accountData);
            $account->email = self::coalesceMap($accountData, PipelinerOppMap::PRIMARY_EMAIL);
            $account->phone = self::coalesceMap($accountData, PipelinerOppMap::PRIMARY_PHONE);
            $account->website = self::coalesceMap($accountData, PipelinerOppMap::HOME_PAGE);
            $account->vendors_cs = self::coalesceMap($accountData, PipelinerOppMap::VENDOR);
            $account->vat_type = match (strtolower(trim((string)self::coalesceMap($accountData, PipelinerOppMap::VAT_TYPE)))) {
                'exempt' => VAT::EXEMPT,
                'vat number' => VAT::VAT_NUMBER,
                'no vat' => VAT::NO_VAT,
                default => null,
            };
            $account->vat = self::coalesceMap($accountData, PipelinerOppMap::VAT);

            if (self::getFlag(self::coalesceMap($accountData, PipelinerOppMap::IS_RESELLER))) {
                $account->flags |= ImportedCompany::IS_RESELLER;
            }

            if (self::getFlag(self::coalesceMap($accountData, PipelinerOppMap::IS_END_USER))) {
                $account->flags |= ImportedCompany::IS_END_USER;
            }

            $newAddressDataOfCompany = $this->mapAddressesOfAccount($accountData, $contacts);
            $newContactDataOfCompany = $this->mapContactsOfAccount($accountData, $contacts);

            $account->setRelation('addresses', $newAddressDataOfCompany);
            $account->setRelation('contacts', $newContactDataOfCompany);

            $primaryContact = $this->mapPrimaryAccountContact($primaryContactName);

            if (!is_null($primaryContact)) {

                $primaryContactFromExisting = $account->contacts
                    ->first(static function (ImportedContact $contact) use ($primaryContact): bool {
                        return mb_strtolower($primaryContact->first_name) === mb_strtolower($contact->first_name)
                            && mb_strtolower($primaryContact->last_name) === mb_strtolower($contact->last_name);
                    });

                if (is_null($primaryContactFromExisting)) {
                    $account->contacts->push($primaryContact);
                } else {
                    $primaryContact = $primaryContactFromExisting;
                }

            }

            $account->primaryContact()->associate($primaryContact);
        });
    }

    public function mapPrimaryAccountContact(string $primaryContactName): ?ImportedContact
    {
        return transform($primaryContactName, static function (string $primaryContactName): ImportedContact {
            $primaryContactName = trim($primaryContactName);

            [$contactFirstName, $contactLastName] = value(function () use ($primaryContactName): array {

                if (str_contains($primaryContactName, ' ')) {
                    return explode(' ', $primaryContactName, 2);
                }

                return [$primaryContactName, null];
            });

            return tap(new ImportedContact(), static function (ImportedContact $contact) use ($contactLastName, $contactFirstName, $primaryContactName): void {
                $contact->contact_type = ContactType::HARDWARE;
                $contact->first_name = $contactFirstName;
                $contact->last_name = $contactLastName;
                $contact->is_verified = true;
            });

        });
    }

    private function mapImportedAddressFromAttributes(?string $type,
                                                      ?string $addressOne,
                                                      ?string $addressTwo,
                                                      ?string $city,
                                                      ?string $zipCode,
                                                      ?string $stateProvince,
                                                      ?string $stateCode,
                                                      ?string $country): ImportedAddress
    {
        $address = new ImportedAddress();

        $address->{$address->getKeyName()} = (string)Uuid::generate(4);

        $address->address_type = match (trim(strtolower($type ?? ''))) {
            'hardware' => AddressType::HARDWARE,
            'software' => AddressType::SOFTWARE,
            default => AddressType::INVOICE
        };

        $address->address_1 = $addressOne;
        $address->address_2 = $addressTwo;

        $address->city = $city;
        $address->post_code = $zipCode;
        $address->state = $stateProvince;
        $address->state_code = $stateCode;

        if (filled($country)) {
            $countryModel = strlen($country) == 2
                ? Country::query()->where('iso_3166_2', $country)->first()
                : Country::query()->where('name', $country)->first();

            $address->country()->associate($countryModel);
        } else {
            $address->country()->disassociate();
        }

        return $address;
    }

    private function mapAddressesOfAccount(array $account, array $contacts): Collection
    {
        return tap(new Collection(), function (Collection $newAddressDataOfCompany) use ($account, $contacts): void {

            $newAddressDataOfCompany[] = $this->mapImportedAddressFromAttributes(
                type: null,
                addressOne: $account['street_address'] ?? null,
                addressTwo: self::coalesceMap($account, ['address_2', 'address_two']),
                city: $account['city'] ?? null,
                zipCode: $account['zip_code'] ?? null,
                stateProvince: self::coalesceMap($account, ['state_province', 'stateprovince']),
                stateCode: $account['state_code'] ?? null,
                country: $account['country'] ?? null,
            );

            foreach ($contacts as $contactData) {

                [$addressOne, $addressTwo] = self::splitStreetAddress($contactData['street_address'] ?? null);

                $newAddressDataOfCompany[] = $this->mapImportedAddressFromAttributes(
                    type: $contactData['type'] ?? null,
                    addressOne: $addressOne,
                    addressTwo: $addressTwo,
                    city: $contactData['city'] ?? null,
                    zipCode: $contactData['zip_code'] ?? null,
                    stateProvince: self::coalesceMap($contactData, ['state_province', 'stateprovince']),
                    stateCode: $contactData['state_code'] ?? null,
                    country: match (strtolower($contactData['type'] ?? '')) {
                        'hardware' => $account['hardware_country_code'] ?? null,
                        'software' => $account['software_country_code'] ?? null,
                        default => $contactData['country'] ?? null,
                    },
                );

            }
        });
    }

    public static function splitStreetAddress(?string $streetAddress): array
    {
        if (blank($streetAddress)) {
            return [null, null];
        }

        $sanitize = static function (?string $value): ?string {
            if (blank($value)) {
                return null;
            }

            return trim($value, " \t\n\r\0\x0B,");
        };

        if (str_contains($streetAddress, "\n")) {
            return array_map($sanitize, explode("\n", $streetAddress, 2));
        }

        return array_map($sanitize, [$streetAddress, null]);
    }

    private function mapContactsOfAccount(array $account, array $contacts): Collection
    {
        $newContactDataOfCompany = new Collection();

        foreach ($contacts as $contactData) {
            $newContactDataOfCompany[] = $this->mapImportedContactFromAttributes(
                type: $contactData['type'] ?? null,
                firstName: $contactData['first_name'] ?? null,
                lastName: $contactData['last_name'] ?? null,
                email: $contactData['primary_e_mail'] ?? null,
                phone: $contactData['primary_phone'] ?? null,
                title: $contactData['titlesalutation'] ?? null,
            );
        }

        return $newContactDataOfCompany;
    }

    private function mapImportedContactFromAttributes(?string $type,
                                                      ?string $firstName,
                                                      ?string $lastName,
                                                      ?string $email,
                                                      ?string $phone,
                                                      ?string $title,
                                                      bool    $isPrimary = false): ImportedContact
    {
        return tap(new ImportedContact(), static function (ImportedContact $contact) use ($type, $isPrimary, $firstName, $lastName, $email, $phone, $title) {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);

            $contact->contact_type = match (strtolower(trim((string)$type))) {
                'software' => ContactType::SOFTWARE,
                default => ContactType::HARDWARE,
            };

            $contact->first_name = $firstName;
            $contact->last_name = $lastName;
            $contact->email = $email;
            $contact->phone = $phone;
            $contact->job_title = $title;
            $contact->is_verified = false;

            $contact->is_primary = $isPrimary;
        });
    }

    public static function resolveCategoryOfCompany(?array $accountData): string
    {
        static $categoryDictionary = [
            'distributor' => AccountCategory::RESELLER,
            'business_partner' => AccountCategory::BUSINESS_PARTNER,
            'reseller' => AccountCategory::RESELLER,
            'end_user' => AccountCategory::END_USER,
        ];

        if (is_null($accountData)) {
            return AccountCategory::RESELLER;
        }

        $categoryDictionaryOfAccountData = Arr::only($accountData, ['distributor', 'business_partner', 'reseller', 'end_user']);

        foreach ($categoryDictionaryOfAccountData as $key => $value) {

            if (self::getFlag($value)) {
                return $categoryDictionary[$key];
            }

        }

        return AccountCategory::RESELLER;
    }

    public static function getFlag(?string $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return trim(mb_strtolower($value)) === 'yes';
    }

    private function getDefaultPipeline(): Pipeline
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->defaultPipeline ??= $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();
    }

    public function mapImportedOpportunityDataFromImportedRow(array $row,
                                                              array $accountsDataDictionary,
                                                              array $accountContactsDataDictionary,
                                                              User  $user): ImportedOpportunityData
    {
        $primaryAccount = $this->mapPrimaryAccountFromImportedRow(
            row: $row,
            accountsDataDictionary: $accountsDataDictionary,
            accountContactsDataDictionary: $accountContactsDataDictionary
        );

        $suppliers = $this->mapSuppliers($row);

        /** @var Pipeline $pipeline */
        $pipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $saleActionName = OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::SALE_ACTION_NAME);
        $stageName = OpportunityDataMapper::resolveStageNameFromSaleAction($saleActionName);

        $pipelineStage = $pipeline->pipelineStages()->where('stage_name', $stageName)->first();

        return new ImportedOpportunityData([
            'pipeline_id' => $pipeline->getKey(),
            'user_id' => $user->getKey(),
            'contract_type_id' => ($this->contractTypeResolver)(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::CONTRACT_TYPE)),
            'account_manager_id' => ($this->accountOwnerResolver)(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::ACCOUNT_MANAGER))?->getKey(),
            'imported_primary_account_id' => $primaryAccount?->getKey(),
            'imported_primary_account_contact_id' => $primaryAccount->primaryContact?->getKey(),
            'project_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::PROJECT_NAME),
            'nature_of_service' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::NATURE_OF_SERVICE),
            'renewal_month' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::RENEWAL_MONTH),
            'renewal_year' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::RENEWAL_YEAR), static fn(string $value) => (int)$value),
            'customer_status' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::CUSTOMER_STATUS),
            'end_user_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::END_USER_NAME),
            'hardware_status' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::HARDWARE_STATUS),
            'region_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::REGION_NAME),
            'opportunity_start_date' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::OPPORTUNITY_START_DATE), static fn($date) => \Illuminate\Support\Carbon::parse($date)),
            'is_opportunity_start_date_assumed' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::IS_OPPORTUNITY_START_DATE_ASSUMED)),
            'opportunity_end_date' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::OPPORTUNITY_END_DATE), static fn($date) => Carbon::parse($date)),
            'is_opportunity_end_date_assumed' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::IS_OPPORTUNITY_END_DATE_ASSUMED)),
            'opportunity_closing_date' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::OPPORTUNITY_CLOSING_DATE), static fn($date) => Carbon::parse($date)),

            'base_opportunity_amount' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::BASE_OPPORTUNITY_AMOUNT), static fn(string $value) => (float)$value),
            'opportunity_amount' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::OPPORTUNITY_AMOUNT), static fn(string $value) => (float)$value),
            'opportunity_amount_currency_code' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::OPPORTUNITY_AMOUNT_CURRENCY_CODE),

            'base_list_price' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::BASE_LIST_PRICE), static fn(string $value) => (float)$value),
            'list_price' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::LIST_PRICE), static fn(string $value) => (float)$value),
            'list_price_currency_code' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::LIST_PRICE_CURRENCY_CODE),

            'base_purchase_price' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::BASE_PURCHASE_PRICE), static fn(string $value) => (float)$value),
            'purchase_price' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::PURCHASE_PRICE), static fn(string $value) => (float)$value),
            'purchase_price_currency_code' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::PURCHASE_PRICE_CURRENCY_CODE),

            'ranking' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::RANKING), static fn(string $value) => (float)$value),
            'estimated_upsell_amount' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::ESTIMATED_UPSELL_AMOUNT), static fn(string $value) => (float)$value),
            'estimated_upsell_amount_currency_code' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::ESTIMATED_UPSELL_AMOUNT_CURRENCY_CODE),

            'personal_rating' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::PERSONAL_RATING),

            'margin_value' => transform(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::MARGIN_VALUE), static fn(string $value) => (float)$value),

            'competition_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::COMPETITION_NAME),

            'service_level_agreement_id' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::SERVICE_LEVEL_AGREEMENT_ID),
            'sale_unit_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::SALE_UNIT_NAME),
            'drop_in' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::DROP_IN),
            'lead_source_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::LEAD_SOURCE_NAME),
            'has_higher_sla' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::HAS_HIGHER_SLA, '')),
            'is_multi_year' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::IS_MULTI_YEAR, '')),
            'has_additional_hardware' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::HAS_ADDITIONAL_HARDWARE, '')),
            'has_service_credits' => OpportunityDataMapper::getFlag(OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::HAS_SERVICE_CREDITS, '')),
            'remarks' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::REMARKS),
            'notes' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::NOTES),
            'sale_action_name' => $saleActionName,

            'campaign_name' => OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::CAMPAIGN_NAME),

            'create_suppliers' => $suppliers,
        ]);
    }

    public function mapPrimaryAccountFromImportedRow(array $row,
                                                     array $accountsDataDictionary,
                                                     array $accountContactsDataDictionary): ?ImportedCompany
    {
        $accountName = $row['primary_account_name'];
        $primaryContactName = $row['primary_contact_name'] ?? '';

        if (blank($accountName)) {
            return null;
        }

        $accountNameHash = md5($accountName);

        $accountData = $accountsDataDictionary[$accountNameHash] ?? [];
        $accountContactData = $accountContactsDataDictionary[$accountNameHash] ?? [];

        $importedCompany = $this->mapImportedCompany(
            accountName: $accountName,
            primaryContactName: $primaryContactName,
            accountData: $accountData,
            contacts: $accountContactData
        );

        $importedCompany->primaryContact?->save();

        $importedCompany->primaryContact()->associate($importedCompany->primaryContact);

        $importedCompany->push();

        $importedCompany->addresses()->attach($importedCompany->addresses);
        $importedCompany->contacts()->attach($importedCompany->contacts);

        return $importedCompany;
    }

    public static function resolveStageNameFromSaleAction(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        sscanf($value, '%d.%s', $order, $stage);

        return $stage ?? $value;
    }

    private static function tryParseDateString(?string $date): ?DateTimeInterface
    {
        if (is_null($date)) {
            return null;
        }

        return Carbon::parse($date);
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}