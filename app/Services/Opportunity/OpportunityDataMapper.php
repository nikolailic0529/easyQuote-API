<?php

namespace App\Services\Opportunity;

use App\Contracts\CauserAware;
use App\DTO\Opportunity\ImportedOpportunityData;
use App\Enum\AccountCategory;
use App\Enum\AddressType;
use App\Enum\ContactType;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerCurrencyIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerDataIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerFieldIntegration;
use App\Integrations\Pipeliner\Models\ContactRelationEntity;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateContactRelationInput;
use App\Integrations\Pipeliner\Models\CreateContactRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateOpportunityInput;
use App\Integrations\Pipeliner\Models\CreateOpptyAccountRelationInput;
use App\Integrations\Pipeliner\Models\CreateOpptyAccountRelationInputCollection;
use App\Integrations\Pipeliner\Models\CurrencyForeignFieldInput;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\StepEntity;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInput;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Models\SalesUnit;
use App\Models\User;
use App\Queries\PipelineQueries;
use App\Services\Opportunity\Models\PipelinerOppMap;
use App\Services\Pipeliner\RuntimeCachedCurrencyEntityResolver;
use App\Services\Pipeliner\RuntimeCachedDataEntityResolver;
use App\Services\Pipeliner\RuntimeCachedFieldApiNameResolver;
use App\Services\Pipeliner\RuntimeCachedFieldEntityResolver;
use App\Services\Pipeliner\RuntimeCachedPipelineResolver;
use App\Services\Pipeliner\RuntimeCachedSalesUnitResolver;
use App\Services\Pipeliner\RuntimeCachedStepResolver;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class OpportunityDataMapper implements CauserAware
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    protected readonly ?Pipeline $defaultPipeline;
    protected ?Model $causer = null;

    protected array $countryNameOfSupplierCache = [];

    protected array $plDataCache = [];

    public function __construct(protected Config                              $config,
                                protected PipelinerDataIntegration            $dataIntegration,
                                protected PipelinerFieldIntegration           $fieldIntegration,
                                protected PipelinerCurrencyIntegration        $currencyIntegration,
                                protected PipelinerAccountIntegration         $accountIntegration,
                                protected RuntimeCachedFieldEntityResolver    $pipelinerFieldResolver,
                                protected RuntimeCachedFieldApiNameResolver   $fieldApiNameResolver,
                                protected RuntimeCachedDataEntityResolver     $pipelinerDataResolver,
                                protected RuntimeCachedCurrencyEntityResolver $pipelinerCurrencyResolver,
                                protected RuntimeCachedSalesUnitResolver      $salesUnitResolver,
                                protected RuntimeCachedPipelineResolver       $pipelineResolver,
                                protected RuntimeCachedStepResolver           $stepResolver,
                                protected PipelineQueries                     $pipelineQueries,
                                protected AccountOwnerResolver                $accountOwnerResolver,
                                protected ContractTypeResolver                $contractTypeResolver)
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
        return tap(new ImportedCompany(), function (ImportedCompany $account) use (
            $primaryContactName,
            $contacts,
            $accountData,
            $accountName
        ): void {
            $account->company_name = $accountName;
            $account->company_category = self::resolveCategoryOfCompany($accountData);
            $account->email = self::coalesceMap($accountData, PipelinerOppMap::PRIMARY_EMAIL);
            $account->phone = self::coalesceMap($accountData, PipelinerOppMap::PRIMARY_PHONE);
            $account->website = self::coalesceMap($accountData, PipelinerOppMap::HOME_PAGE);
            $account->vendors_cs = self::coalesceMap($accountData, PipelinerOppMap::VENDOR);
            $account->vat_type = self::coalesceMap($accountData, PipelinerOppMap::VAT_TYPE);
            $account->vat = self::coalesceMap($accountData, PipelinerOppMap::VAT);

            if (self::getFlag(self::coalesceMap($accountData, PipelinerOppMap::IS_RESELLER))) {
                $account->flags |= ImportedCompany::IS_RESELLER;
            }

            if (self::getFlag(self::coalesceMap($accountData, PipelinerOppMap::IS_END_USER))) {
                $account->flags |= ImportedCompany::IS_END_USER;
            }

            [$newAddressDataOfCompany,
                $newContactDataOfCompany] = $this->mapImportedAddressesContactsFromAccount($accountData, $contacts);

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

            return tap(new ImportedContact(), static function (ImportedContact $contact) use (
                $contactLastName,
                $contactFirstName,
                $primaryContactName
            ): void {
                $contact->contact_type = ContactType::HARDWARE;
                $contact->first_name = $contactFirstName;
                $contact->last_name = $contactLastName;
                $contact->is_verified = true;
            });

        });
    }

    private function mapImportedAddressFromAttributes(?string $type,
                                                      ?string $plReference,
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
        $address->pl_reference = $plReference;

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
            $address->country()->associate(
                Country::query()->where('name', $country)->first()
            );
        } else {
            $address->country()->disassociate();
        }

        return $address;
    }

    private function mapImportedAddressesContactsFromAccount(array $account, array $contacts): array
    {
        $newAddresses = new Collection();
        $newContacts = new Collection();

        $newAddresses[] = $this->mapImportedAddressFromAttributes(
            type: null,
            plReference: null,
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

            $newAddress = $this->mapImportedAddressFromAttributes(
                type: $contactData['type'] ?? null,
                plReference: null,
                addressOne: $addressOne,
                addressTwo: $addressTwo,
                city: $contactData['city'] ?? null,
                zipCode: $contactData['zip_code'] ?? null,
                stateProvince: self::coalesceMap($contactData, ['state_province', 'stateprovince']),
                stateCode: $contactData['state_code'] ?? null,
                country: $contactData['country'] ?? null,
            );

            $newContact = $this->mapImportedContactFromAttributes(
                plReference: null,
                firstName: $contactData['first_name'] ?? null,
                lastName: $contactData['last_name'] ?? null,
                email: $contactData['primary_e_mail'] ?? null,
                phone: $contactData['primary_phone'] ?? null,
                title: $contactData['titlesalutation'] ?? null,
            );

            $newAddresses[] = $newAddress;
            $newContacts[] = $newContact->address()->associate($newAddress->getKey());
        }

        return [$newAddresses, $newContacts];
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

    private function mapImportedContactFromAttributes(?string $plReference,
                                                      ?string $firstName,
                                                      ?string $lastName,
                                                      ?string $email,
                                                      ?string $phone,
                                                      ?string $title,
                                                      bool    $isPrimary = false): ImportedContact
    {
        return tap(new ImportedContact(), static function (ImportedContact $contact) use (
            $plReference,
            $isPrimary,
            $firstName,
            $lastName,
            $email,
            $phone,
            $title
        ) {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
            $contact->pl_reference = $plReference;

            $contact->contact_type = ContactType::HARDWARE;
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

        $categoryDictionaryOfAccountData = Arr::only($accountData, ['distributor', 'business_partner', 'reseller',
            'end_user']);

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

    private function resolveValuesFromQueryableDataFieldsOfOpportunityEntity(OpportunityEntity $entity): BaseCollection
    {
        $customFieldsCollection = collect(Arr::dot($entity->customFields));

        $dataFieldIds = $customFieldsCollection->only([
            'cfAccountManagerId',
            'cfSupportDurationMonthsId',
            'cfNatureOfServiceId',
            'cfNatureOfService1Id',
            'cfRenMonthId',
            'cfRenYearId',
            'cfCustomerStatusId',
            'cfHwStatusId',
            'cfRegionId',
            'cfPersonalRatingId',
            'cfDropInId',
            'cfLeadSourceId',
            'cfCampaignId',
            'cfOpportunityTypeId',
            'cfVatTypeId',
        ])
            ->filter('filled');


        return $dataFieldIds
            ->map(fn(string $id) => ($this->pipelinerDataResolver)($id))
            ->keyBy('id');
    }

    private function resolveValuesFromQueryableCurrencyFieldsOfOpportunityEntity(OpportunityEntity $entity): BaseCollection
    {
        $customFieldsCollection = collect(Arr::dot($entity->customFields));

        $dataFieldIds = $customFieldsCollection->only([
            'cfPurchasePrice.currencyId',
            'cfListPrice.currencyId',
        ])
            ->push($entity->value->currencyId)
            ->filter('filled');

        return collect($this->currencyIntegration->getByIds(...$dataFieldIds->values()))->keyBy('id');
    }

    private function getDefaultPipeline(): Pipeline
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->defaultPipeline ??= $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();
    }

    private function resolvePipelineStageFromStepEntity(StepEntity $entity): ?PipelineStage
    {
        $pl = $this->getDefaultPipeline();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $pl->pipelineStages()->where('stage_name', $entity->name)->first()
            ?? $pl->pipelineStages()->where('stage_percentage', $entity->percent)->first();
    }

    public function mapOpportunityFromOpportunityEntity(OpportunityEntity $entity, Collection $accounts): Opportunity
    {
        return tap(new Opportunity(), function (Opportunity $opportunity) use ($entity, $accounts): void {

            /** @var DataEntity[] $dataFieldMap */
            $dataFieldMap = $this->resolveValuesFromQueryableDataFieldsOfOpportunityEntity($entity);

            $resolveDataFieldOptionName = static function (?string $id) use ($dataFieldMap): ?string {
                if (is_null($id) || !isset($dataFieldMap[$id])) {
                    return null;
                }

                return $dataFieldMap[$id]->optionName;
            };

            $resolveDataFieldCalcValue = static function (?string $id) use ($dataFieldMap): ?string {
                if (is_null($id) || !isset($dataFieldMap[$id])) {
                    return null;
                }

                return $dataFieldMap[$id]->calcValue;
            };

            $currencyFieldMap = $this->resolveValuesFromQueryableCurrencyFieldsOfOpportunityEntity($entity);

            $resolveCurrencyFieldValue = static function (?string $id) use ($currencyFieldMap): ?string {
                if (is_null($id) || !isset($currencyFieldMap[$id])) {
                    return null;
                }

                return $currencyFieldMap[$id]->code;
            };

            $opportunity->{$opportunity->getKeyName()} = (string)Uuid::generate(4);
            $opportunity->pl_reference = $entity->id;
            $opportunity->user()->associate($this->causer);
            $opportunity->salesUnit()->associate(
                SalesUnit::query()->where('unit_name', $entity->unit->name)->first()
            );
            $opportunity->pipeline()->associate($this->getDefaultPipeline());
            $opportunity->pipelineStage()->associate($this->resolvePipelineStageFromStepEntity($entity->step));
            $opportunity->contractType()->associate(($this->contractTypeResolver)($resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfOpportunityTypeId'))));

            $accountManagerName = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfAccountManagerId'));
            $opportunity->accountManager()->associate(($this->accountOwnerResolver)($accountManagerName));

            /** @var Company|null $primaryAccount */
            /** @var Company|null $endUser */
            [$primaryAccount, $endUser] = value(static function () use ($entity, $accounts): array {
                $accounts = Collection::make($accounts->all());

                $primaryAccountKey = $accounts
                    ->search(static function (Company $company) use ($entity): bool {
                        return $entity->primaryAccount->id === $company->pl_reference;
                    });

                return [
                    false !== $primaryAccountKey ? $accounts->pull($primaryAccountKey) : null,
                    $accounts->shift(),
                ];
            });

            /** @var Contact|null $primaryContact */
            $primaryContact = value(static function () use ($entity, $primaryAccount): ?Contact {
                if (null === $primaryAccount) {
                    return null;
                }

                if (null === $entity->primaryContact) {
                    return null;
                }

                return $primaryAccount->contacts
                    ->first(static fn(Contact $contact): bool => $entity->primaryContact->id === $contact->pl_reference);
            });

            $opportunity->primaryAccount()->associate($primaryAccount);
            $opportunity->primaryAccountContact()->associate($primaryContact);
            $opportunity->endUser()->associate($endUser);

            $opportunity->project_name = $entity->name;
            $opportunity->nature_of_service = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfNatureOfServiceId'));
            $opportunity->renewal_month = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfRenMonthId'));
            $opportunity->renewal_year = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfRenYearId'));
            $opportunity->customer_status = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfCustomerStatusId'));
            $opportunity->end_user_name = Arr::get($entity->customFields, 'cfEnduser');
            $opportunity->hardware_status = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfHwStatusId'));
            $opportunity->region_name = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfRegionId'));
            $opportunity->opportunity_start_date = self::tryParseDateString(Arr::get($entity->customFields, 'cfStartDate'));
            $opportunity->is_opportunity_start_date_assumed = (bool)Arr::get($entity->customFields, 'cfStartDateAssumed');
            $opportunity->opportunity_end_date = self::tryParseDateString(Arr::get($entity->customFields, 'cfEndDate'));
            $opportunity->is_opportunity_end_date_assumed = (bool)Arr::get($entity->customFields, 'cfEndDateAssumed');
            $opportunity->opportunity_closing_date = $entity->closingDate;
            $opportunity->expected_order_date = Arr::get($entity->customFields, 'cfDelayedClosing');
            $opportunity->is_contract_duration_checked = (bool)Arr::get($entity->customFields, 'cfSupportDuration', false);
            $opportunity->contract_duration_months = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfSupportDurationMonthsId'));
            $opportunity->opportunity_amount = $entity->value->valueForeign;
            $opportunity->base_opportunity_amount = $entity->value->baseValue;
            $opportunity->opportunity_amount_currency_code = $resolveCurrencyFieldValue($entity->value->currencyId);
            $opportunity->purchase_price = Arr::get($entity->customFields, 'cfPurchasePrice.valueForeign');
            $opportunity->base_purchase_price = Arr::get($entity->customFields, 'cfPurchasePrice.baseValue');
            $opportunity->purchase_price_currency_code = $resolveCurrencyFieldValue(Arr::get($entity->customFields, 'cfPurchasePrice.currencyId'));
            $opportunity->estimated_upsell_amount = Arr::get($entity->customFields, 'cfEstimatedUpsellAmount');
            $opportunity->estimated_upsell_amount_currency_code = null;
            $opportunity->list_price = Arr::get($entity->customFields, 'cfListPrice.valueForeign');
            $opportunity->base_list_price = Arr::get($entity->customFields, 'cfListPrice.baseValue');
            $opportunity->list_price_currency_code = $resolveCurrencyFieldValue(Arr::get($entity->customFields, 'cfListPrice.currencyId'));
            $opportunity->personal_rating = $resolveDataFieldCalcValue(Arr::get($entity->customFields, 'cfPersonalRatingId'));
            $opportunity->margin_value = Arr::get($entity->customFields, 'cfMargin1');
            $opportunity->service_level_agreement_id = Arr::get($entity->customFields, 'cfSla');
            $opportunity->sale_unit_name = $entity->unit->name;
            $opportunity->drop_in = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfDropInId'));
            $opportunity->lead_source_name = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfLeadSourceId'));
            $opportunity->has_higher_sla = (bool)Arr::get($entity->customFields, 'cfHigherSla');
            $opportunity->is_multi_year = (bool)Arr::get($entity->customFields, 'cfConfiguration1n');
            $opportunity->has_additional_hardware = (bool)Arr::get($entity->customFields, 'cfAdditionalHardware');
            $opportunity->has_service_credits = (bool)Arr::get($entity->customFields, 'cfServiceCredits');
            $opportunity->remarks = Arr::get($entity->customFields, 'cfRemark');
            $opportunity->sale_action_name = $entity->step->getQualifiedStepName();
            $opportunity->ranking = $entity->ranking;
            $opportunity->campaign_name = $resolveDataFieldOptionName(Arr::get($entity->customFields, 'cfCampaignId'));
            $opportunity->competition_name = Arr::get($entity->customFields, 'cfCompetition');
            $opportunity->customer_order_date = Arr::get($entity->customFields, 'cfCustOrderDate');
            $opportunity->notes = $entity->description;

//            $opportunity->{$opportunity->getDeletedAtColumn()} = $opportunity->freshTimestamp();

            $opportunity->setRelation('opportunitySuppliers', $this->mapSuppliersFromOpportunityEntity($entity));

            $opportunity->opportunitySuppliers->each(static function (OpportunitySupplier $supplier) use ($opportunity
            ): void {
                $supplier->opportunity()->associate($opportunity->getKey());
            });
        });
    }

    public function mergeAttributesFrom(Opportunity $opportunity, Opportunity $another): void
    {
        $toBeMergedBelongsToRelations = [
            'salesUnit',
            'pipelineStage',
            'contractType',
            'accountManager',
            'primaryAccount',
            'primaryAccountContact',
            'endUser',
        ];

        foreach ($toBeMergedBelongsToRelations as $relation) {
            if (null !== $another->$relation) {
                $opportunity->$relation()->associate($another->$relation);
            }
        }

        $toBeMergedAttributes = [
            'project_name',
            'nature_of_service',
            'renewal_month',
            'renewal_year',
            'customer_status',
            'end_user_name',
            'hardware_status',
            'region_name',
            'opportunity_start_date',
            'is_opportunity_start_date_assumed',
            'opportunity_end_date',
            'is_opportunity_end_date_assumed',
            'opportunity_closing_date',
            'is_contract_duration_checked',
            'contract_duration_months',
            'opportunity_amount',
            'base_opportunity_amount',
            'opportunity_amount_currency_code',
            'purchase_price',
            'base_purchase_price',
            'purchase_price_currency_code',
            'estimated_upsell_amount',
            'estimated_upsell_amount_currency_code',
            'list_price',
            'base_list_price',
            'list_price_currency_code',
            'personal_rating',
            'margin_value',
            'service_level_agreement_id',
            'sale_unit_name',
            'drop_in',
            'lead_source_name',
            'has_higher_sla',
            'is_multi_year',
            'has_additional_hardware',
            'has_service_credits',
            'remarks',
            'sale_action_name',
            'ranking',
            'campaign_name',
            'competition_name',
            'notes',
            'expected_order_date',
            'customer_order_date',
        ];

        foreach ($toBeMergedAttributes as $attribute) {
            if (null !== $another->$attribute) {
                $opportunity->$attribute = $another->$attribute;
            }
        }

        $matchSupplier = static function (OpportunitySupplier $needle, Collection $suppliers): ?OpportunitySupplier {
            return $suppliers->first(static function (OpportunitySupplier $supplier) use ($needle) {
                return
                    null === $supplier->{$supplier->getDeletedAtColumn()}
                    && 0 === strcasecmp($needle->supplier_name, $supplier->supplier_name)
                    && 0 === strcasecmp($needle->country_name, $supplier->country_name);
            });
        };

        $opportunitySuppliersToBeDeleted = $opportunity->opportunitySuppliers->filter(static function (OpportunitySupplier $supplier) use
        (
            $matchSupplier,
            $another
        ): bool {
            return null === $matchSupplier($supplier, $another->opportunitySuppliers);
        });

        $opportunitySuppliersToBeDeleted->each(static function (OpportunitySupplier $supplier): void {
            $supplier->{$supplier->getDeletedAtColumn()} = $supplier->freshTimestamp();
        });

        foreach ($another->opportunitySuppliers as $anotherSupplier) {
            $matchingSupplier = $matchSupplier($anotherSupplier, $opportunity->opportunitySuppliers);

            if (null === $matchingSupplier) {
                $opportunity->opportunitySuppliers
                    ->push(
                        tap($anotherSupplier->replicate(), static function (OpportunitySupplier $supplier) use (
                            $opportunity
                        ): void {
                            $supplier->opportunity()->associate($opportunity->getKey());
                        })
                    );
            } else {
                tap($matchingSupplier, static function (OpportunitySupplier $supplier) use ($anotherSupplier): void {
                    $supplier->supplier_name = $anotherSupplier->supplier_name;
                    $supplier->country_name = $anotherSupplier->country_name;
                    $supplier->contact_name = $anotherSupplier->contact_name;
                    $supplier->contact_email = $anotherSupplier->contact_email;
                });
            }
        }
    }

    public function mapSuppliersFromOpportunityEntity(OpportunityEntity $entity): Collection
    {
        $customFields = $entity->customFields;

        $mapping = config('pipeliner.custom_fields.suppliers', []);

        $suppliers = collect($mapping)
            ->map(static function (array $supplierFieldMap) use ($customFields): array {
                [
                    'country_id' => $countryFieldApiName,
                    'distributor_id' => $distributorFieldApiName,
                    'contact_name' => $contactNameFieldApiName,
                    'email_address' => $emailAddressFieldApiName,
                ] = $supplierFieldMap;

                return [
                    'country_id' => Arr::get($customFields, $countryFieldApiName),
                    'distributor_id' => Arr::get($customFields, $distributorFieldApiName),
                    'contact_name' => Arr::get($customFields, $contactNameFieldApiName),
                    'email_address' => Arr::get($customFields, $emailAddressFieldApiName),
                ];
            })
            ->filter(static function (array $supplierFields): bool {
                return collect($supplierFields)->contains(static fn(mixed $value) => filled($value));
            })
            ->map(function (array $supplierFields): OpportunitySupplier {
                return tap(new OpportunitySupplier(), function (OpportunitySupplier $supplier) use ($supplierFields
                ): void {
                    $supplier->supplier_name = ($this->pipelinerDataResolver)($supplierFields['distributor_id'])?->optionName;
                    $supplier->country_name = ($this->pipelinerDataResolver)($supplierFields['country_id'])?->optionName;
                    $supplier->contact_name = $supplierFields['contact_name'];
                    $supplier->contact_email = $supplierFields['email_address'];
                });
            })
            ->values();

        return new Collection($suppliers->all());
    }

    public function projectOpportunitySuppliersToCustomFields(Opportunity $opportunity): array
    {
        $mapping = config('pipeliner.custom_fields.suppliers', []);

        $suppliers = $opportunity->opportunitySuppliers->take(count($mapping));

        $fields = $suppliers
            ->reduce(function (array $fields, OpportunitySupplier $supplier, int $i) use ($mapping): array {
                $countryFieldApiName = $mapping[$i]['country_id'];
                $distributorFieldApiName = $mapping[$i]['distributor_id'];
                $contactNameFieldApiName = $mapping[$i]['contact_name'];
                $emailAddressFieldApiName = $mapping[$i]['email_address'];

                $supplierFields = [
                    $countryFieldApiName => $this->resolveDataEntityByOptionName('Opportunity', Str::snake($countryFieldApiName), $supplier->country_name)?->id,
                    $distributorFieldApiName => $this->resolveDataEntityByOptionName('Opportunity', Str::snake($distributorFieldApiName), $supplier->supplier_name)?->id,
                    $contactNameFieldApiName => $supplier->contact_name,
                    $emailAddressFieldApiName => $supplier->contact_email,
                ];

                return $fields + $supplierFields;
            }, []);

        // Pad the missing suppliers to empty the controls.
        $emptyFields = collect($mapping)
            ->slice($suppliers->count())
            ->reduce(static function (array $fields, array $supplierFieldMap) {
                $supplierFields = collect($supplierFieldMap)->mapWithKeys(static function (string $cf): array {
                    return [$cf => ''];
                })->all();

                return $fields + $supplierFields;
            }, []);

        $fields = $fields + $emptyFields;

        return collect($fields)
            ->mapWithKeys(function (mixed $value, string $reference): array {
                $key = ($this->fieldApiNameResolver)('Opportunity', $reference);

                return [$key => $value];
            })
            ->all();
    }

    public function projectOpportunityAttrsToCustomFields(Opportunity $opportunity): array
    {
        $oppFieldCustomFieldMap = [
            'hardware_status' => 'cf_hw_status_id',
            'nature_of_service' => 'cf_nature_of_service_id',
            'renewal_month' => 'cf_ren_month_id',
            'renewal_year' => 'cf_ren_year_id',
            'customer_status' => 'cf_customer_status_id',
            'region_name' => 'cf_region_id',
            'contract_duration_months' => 'cf_support_duration_months_id',
            'contractType.type_short_name' => 'cf_opportunity_type_id',
            'campaign_name' => 'cf_campaign_id',
            'drop_in' => 'cf_drop_in_id',
            'lead_source_name' => 'cf_lead_source_id',
            'accountManager.user_fullname' => 'cf_account_manager_id',
        ];

        $resolvedDataOfCustomFields = collect($oppFieldCustomFieldMap)
            ->map(fn(string $apiName, string $path): ?DataEntity => $this->resolveDataEntityByOptionName(
                entityName: 'Opportunity',
                apiName: $apiName,
                optionName: transform(data_get($opportunity, $path), fn(mixed $v): string => (string)$v),
            ));

        $purchasePriceCurrencyEntity = ($this->pipelinerCurrencyResolver)($opportunity->purchase_price_currency_code ?? setting('base_currency'));
        $listPriceCurrencyEntity = ($this->pipelinerCurrencyResolver)($opportunity->list_price_currency_code ?? setting('base_currency'));

        $customFields = [
            'cfPersonalRatingId' => $this->resolveDataEntityByCalcValue('Opportunity', 'cf_personal_rating_id', (float)$opportunity->personal_rating)?->id,
            'cfDelayedClosing' => isset($opportunity->expected_order_date) ? Carbon::parse($opportunity->expected_order_date)->toDateString() : null,
            'cfRemark' => $opportunity->remarks,
            'cfStartDate' => $opportunity->opportunity_start_date,
            'cfEndDate' => $opportunity->opportunity_end_date,
            'cfStartDateAssumed' => (bool)$opportunity->is_opportunity_start_date_assumed,
            'cfEndDateAssumed' => (bool)$opportunity->is_opportunity_end_date_assumed,
            'cfNatureOfServiceId' => $resolvedDataOfCustomFields['nature_of_service']?->id,
            'cfRenMonthId' => $resolvedDataOfCustomFields['renewal_month']?->id,
            'cfRenYearId' => $resolvedDataOfCustomFields['renewal_year']?->id,
            'cfCustomerStatusId' => $resolvedDataOfCustomFields['customer_status']?->id,
            'cfEnduser' => $opportunity->end_user_name,
            'cfHwStatusId' => $resolvedDataOfCustomFields['hardware_status']?->id,
            'cfRegionId' => $resolvedDataOfCustomFields['region_name']?->id,
            'cfSupportDuration' => (bool)$opportunity->is_contract_duration_checked,
            'cfSupportDurationMonthsId' => $resolvedDataOfCustomFields['contract_duration_months']?->id,
            'cfOpportunityTypeId' => $resolvedDataOfCustomFields['contractType.type_short_name']?->id,
            'cfPurchasePrice' => [
                'baseValue' => $opportunity->base_purchase_price,
                'valueForeign' => $opportunity->purchase_price,
                'currencyId' => $purchasePriceCurrencyEntity?->id,
            ],
            'cfListPrice' => [
                'baseValue' => $opportunity->base_list_price,
                'valueForeign' => $opportunity->list_price,
                'currencyId' => $listPriceCurrencyEntity?->id,
            ],
            'cfMargin1' => $opportunity->margin_value,
            'cfCampaignId' => $resolvedDataOfCustomFields['campaign_name']?->id,
            'cfSla' => $opportunity->service_level_agreement_id,
            'cfCompetition' => $opportunity->competition_name,
            'cfDropInId' => $resolvedDataOfCustomFields['drop_in']?->id,
            'cfLeadSourceId' => $resolvedDataOfCustomFields['lead_source_name']?->id,
            'cfEstimatedUpsellAmount' => $opportunity->estimated_upsell_amount,
            'cfHigherSla' => (bool)$opportunity->has_higher_sla,
            'cfConfiguration1n' => (bool)$opportunity->is_multi_year,
            'cfAdditionalHardware' => (bool)$opportunity->has_additional_hardware,
            'cfServiceCredits' => (bool)$opportunity->has_service_credits,
            'cfAccountManagerId' => $resolvedDataOfCustomFields['accountManager.user_fullname']?->id,
            'cfCustOrderDate' => isset($opportunity->customer_order_date)
                ? Carbon::parse($opportunity->customer_order_date)->toDateString()
                : null,
        ];

        $purchasePriceIsEmpty = collect($customFields['cfPurchasePrice'])
            ->contains(static fn(mixed $mixed) => blank($mixed));

        if ($purchasePriceIsEmpty) {
            unset($customFields['cfPurchasePrice']);
        }

        $listPriceIsEmpty = collect($customFields['cfListPrice'])
            ->contains(static fn(mixed $mixed) => blank($mixed));

        if ($listPriceIsEmpty) {
            unset($customFields['cfListPrice']);
        }

        return collect($customFields)->mapWithKeys(function (mixed $value, string $reference) {
            $key = ($this->fieldApiNameResolver)('Opportunity', $reference);

            return [$key => $value];
        })->all();
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException
     */
    public function resolveDataEntityByOptionName(string $entityName, string $apiName, ?string $optionName): ?DataEntity
    {
        $field = ($this->pipelinerFieldResolver)(
            FieldFilterInput::new()
                ->entityName(EntityFilterStringField::ieq($entityName))
                ->apiName(EntityFilterStringField::ieq($apiName))
        );

        if (is_null($field)) {
            return null;
        }

        foreach ($field->dataSet as $dataEntity) {
            if ($optionName === $dataEntity->optionName) {
                return $dataEntity;
            }
        }

        return null;
    }

    public function resolveDataEntityByCalcValue(string $entityName, string $apiName, float $calcValue): ?DataEntity
    {
        $field = ($this->pipelinerFieldResolver)(
            FieldFilterInput::new()
                ->entityName(EntityFilterStringField::ieq($entityName))
                ->apiName(EntityFilterStringField::ieq($apiName))
        );

        return collect($field?->dataSet)
            ->first(static function (DataEntity $entity) use ($calcValue): bool {
                return $entity->calcValue === $calcValue;
            });
    }

    public function mapImportedOpportunityDataFromImportedRow(array $row,
                                                              array $accountsDataDictionary,
                                                              array $accountContactsDataDictionary): ImportedOpportunityData
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

        $unitName = OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::SALE_UNIT_NAME);
        $unit = SalesUnit::query()->where('unit_name', $unitName)->first();

        return new ImportedOpportunityData([
            'pipeline_id' => $pipeline->getKey(),
            'pipeline_stage_id' => $pipelineStage?->getKey(),
            'sales_unit_id' => $unit?->getKey(),
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
            'sale_unit_name' => $unitName,
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

    public function mapPipelinerCreateOpportunityInput(Opportunity $opportunity): CreateOpportunityInput
    {
        $customFields = array_merge(
            $this->projectOpportunitySuppliersToCustomFields($opportunity),
            $this->projectOpportunityAttrsToCustomFields($opportunity)
        );

        $pipeline = ($this->pipelineResolver)($opportunity->pipeline->pipeline_name);

        $step = null;

        if (null !== $opportunity->pipelineStage) {
            $step = ($this->stepResolver)($opportunity->pipelineStage?->stage_name, $pipeline->id);
        }

        $currencyOfValue = ($this->pipelinerCurrencyResolver)($opportunity->opportunity_amount_currency_code ?? setting('base_currency'));

        $closingDate = transform($opportunity->opportunity_closing_date, static fn(string $date): \DateTimeImmutable => Carbon::parse($date)->toDateTimeImmutable());

        $accountRelations = [];

        if (null !== $opportunity->primaryAccount) {
            $accountRelations[] = new CreateOpptyAccountRelationInput((string)$opportunity->primaryAccount->pl_reference, isPrimary: true);
        }

        // Pipeliner doesn't allow to push the same account relation,
        // even if it's used as non-primary account.
        if (null !== $opportunity->endUser && $opportunity->endUser->isNot($opportunity->primaryAccount)) {
            $accountRelations[] = new CreateOpptyAccountRelationInput((string)$opportunity->endUser->pl_reference, isPrimary: false);
        }

        $accountRelationCollection = new CreateOpptyAccountRelationInputCollection(...$accountRelations);
        $contactRelationCollection = new CreateContactRelationInputCollection(...array_values($this->collectContactRelationInputsFromOpportunity($opportunity)));

        $documents = $opportunity->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);

        return new CreateOpportunityInput(
            closingDate: $closingDate,
            name: $opportunity->project_name,
            ownerId: (string)$opportunity->owner?->pl_reference,
            stepId: $step?->id ?? '',
            accountRelations: $accountRelationCollection->valid() ? $accountRelationCollection : InputValueEnum::Miss,
            contactRelations: $contactRelationCollection->valid() ? $contactRelationCollection : InputValueEnum::Miss,
            documents: $documents,
            description: $opportunity->notes ?? '',
            unitId: $opportunity->salesUnit?->pl_reference ?? '',
            customFields: json_encode($customFields),
            value: new CurrencyForeignFieldInput(
                baseValue: $opportunity->base_opportunity_amount,
                currencyId: $currencyOfValue?->id ?? '',
                valueForeign: $opportunity->opportunity_amount
            )
        );
    }

    public function mapPipelinerUpdateOpportunityInput(Opportunity       $opportunity,
                                                       OpportunityEntity $oppEntity): UpdateOpportunityInput
    {
        $customFields = $oppEntity->customFields;

        $customFields = array_merge($customFields, $this->projectOpportunitySuppliersToCustomFields($opportunity));

        $customFields = array_merge($customFields, $this->projectOpportunityAttrsToCustomFields($opportunity));

        $step = ($this->stepResolver)($opportunity->pipelineStage?->stage_name, $oppEntity->step->pipeline->id);
        $stepId = $step?->id;

        $ownerId = (string)$opportunity->owner?->pl_reference;

        $currencyOfValue = ($this->pipelinerCurrencyResolver)($opportunity->opportunity_amount_currency_code ?? setting('base_currency'));

        $closingDate = transform($opportunity->opportunity_closing_date, static fn(string $date): \DateTimeImmutable => Carbon::parse($date)->toDateTimeImmutable());

        $description = $opportunity->notes ?? '';

        $name = $opportunity->project_name;

        $value = new CurrencyForeignFieldInput(
            baseValue: $opportunity->base_opportunity_amount,
            currencyId: $currencyOfValue?->id,
            valueForeign: $opportunity->opportunity_amount
        );

        $customFieldsJson = json_encode($customFields);

        if (Carbon::instance($closingDate)->equalTo($oppEntity->closingDate)) {
            $closingDate = InputValueEnum::Miss;
        }

        if ($description === $oppEntity->description) {
            $description = InputValueEnum::Miss;
        }

        if ($name === $oppEntity->name) {
            $name = InputValueEnum::Miss;
        }

        if (blank($ownerId) || $ownerId === $oppEntity->owner?->id) {
            $ownerId = InputValueEnum::Miss;
        }

        if ($stepId === $oppEntity->step?->id) {
            $stepId = InputValueEnum::Miss;
        }

        $customFieldsDiff = array_udiff_assoc($customFields, $oppEntity->customFields, function (mixed $a,
                                                                                                 mixed $b): int {
            if ($a === null || $b === null) {
                return $a === $b ? 0 : 1;
            }

            return $a <=> $b;
        });

        if (0 === count($customFieldsDiff)) {
            $customFieldsJson = InputValueEnum::Miss;
        }

        if ($value->baseValue === $oppEntity->value->baseValue
            && $value->valueForeign === $oppEntity->value->valueForeign
            && $value->currencyId === $oppEntity->value->currencyId) {
            $value = InputValueEnum::Miss;
        }

        $accountRelations = [];

        if (null !== $opportunity->primaryAccount) {
            $accountRelations[$opportunity->primaryAccount->pl_reference] = new CreateOpptyAccountRelationInput((string)$opportunity->primaryAccount->pl_reference, isPrimary: true);
        }

        // Pipeliner doesn't allow to push the same account relation,
        // even if it's used as non-primary account.
        if (null !== $opportunity->endUser && $opportunity->endUser->isNot($opportunity->primaryAccount)) {
            $accountRelations[$opportunity->endUser->pl_reference] = new CreateOpptyAccountRelationInput((string)$opportunity->endUser->pl_reference, isPrimary: false);
        }

        /** @var CreateOpptyAccountRelationInput[] $accountRelations */
        $accountRelations = array_values($accountRelations);

        if (1 === count($accountRelations) && false === $accountRelations[0]->isPrimary) {
            $accountRelations = [
                new CreateOpptyAccountRelationInput($accountRelations[0]->accountId, true),
            ];
        }

        $accountRelationCollection = new CreateOpptyAccountRelationInputCollection(...$accountRelations);

        $accountRelationChanged = value(static function () use ($oppEntity, $accountRelationCollection): bool {
            if (count($oppEntity->accountRelations) !== $accountRelationCollection->count()) {
                return true;
            }

            $map = collect($oppEntity->accountRelations)->pluck('isPrimary', 'accountId')->all();

            foreach ($accountRelationCollection as $rel) {
                if (!key_exists($rel->accountId, $map) || $map[$rel->accountId] !== $rel->isPrimary) {
                    return true;
                }
            }

            return false;
        });

        $accountRelationCollection->rewind();

        if (false === $accountRelationChanged) {
            $accountRelationCollection = InputValueEnum::Miss;
        }

        $contactRelationMap = $this->collectContactRelationInputsFromOpportunity($opportunity);
        $updatedContactRelationsContainPrimaryContact = collect($contactRelationMap)
            ->containsStrict(static function (CreateContactRelationInput $input): bool {
                return $input->isPrimary;
            });

        $existingContactRelationMap = collect($oppEntity->contactRelations)
            ->mapWithKeys(static fn(ContactRelationEntity $rel): array => [$rel->contact->id => new CreateContactRelationInput(
                contactId: $rel->contact->id,
                isPrimary: $updatedContactRelationsContainPrimaryContact ? false : $rel->isPrimary,
            )])
            ->all();

        $contactRelations = [
            ...$existingContactRelationMap,
            ...$contactRelationMap,
        ];

//        // Set the first contact relation as primary when nothing is set
//        if (!empty($contactRelations) && collect($contactRelations)->doesntContain('isPrimary', '===', true)) {
//            /** @var CreateContactRelationInput $headContactRelation */
//            $headContactRelation = head($contactRelations);
//
//            $contactRelations[$headContactRelation->contactId] = new CreateContactRelationInput(
//                contactId: $headContactRelation->contactId,
//                isPrimary: true
//            );
//        }

        $contactRelationsDiff = array_udiff_assoc($contactRelations, $existingContactRelationMap, function (mixed $a,
                                                                                                            mixed $b): int {
            if ($a === null || $b === null) {
                return $a === $b ? 0 : 1;
            }

            return $a <=> $b;
        });

        $contactRelationCollection = count($contactRelations) > 0 && (count($contactRelationsDiff) > 0 || count($contactRelations) !== count($existingContactRelationMap))
            ? new CreateContactRelationInputCollection(...array_values($contactRelations))
            : InputValueEnum::Miss;

        $documents = $opportunity->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);

        $unitId = $opportunity->salesUnit?->pl_reference ?? InputValueEnum::Miss;
        if ($unitId === $oppEntity->unit->id) {
            $unitId = InputValueEnum::Miss;
        }

        return new UpdateOpportunityInput(
            id: $oppEntity->id,
            closingDate: $closingDate,
            description: $description,
            name: $name,
            ownerId: $ownerId,
            stepId: $stepId,
            unitId: $unitId,
            customFields: $customFieldsJson,
            value: $value,
            accountRelations: $accountRelationCollection,
            contactRelations: $contactRelationCollection,
            documents: $documents,
        );
    }

    /**
     * @param Opportunity $opportunity
     * @return CreateContactRelationInput[]
     */
    private function collectContactRelationInputsFromOpportunity(Opportunity $opportunity): array
    {
        if (null === $opportunity->primaryAccountContact) {
            return [];
        }

        return [
            $opportunity->primaryAccountContact->pl_reference => new CreateContactRelationInput(
                contactId: (string)$opportunity->primaryAccountContact->pl_reference,
                isPrimary: true,
            ),
        ];
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

        $importedCompany->addresses->each->save();
        $importedCompany->primaryContact?->save();
        $importedCompany->contacts->each->save();

        $importedCompany->primaryContact()->associate($importedCompany->primaryContact);

        $importedCompany->push();

        $importedCompany->contacts()->attach($importedCompany->contacts);
        $importedCompany->addresses()->attach($importedCompany->addresses);

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