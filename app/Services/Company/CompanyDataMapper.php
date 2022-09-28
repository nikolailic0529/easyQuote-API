<?php

namespace App\Services\Company;

use App\Enum\AddressType;
use App\Enum\CompanyCategoryEnum;
use App\Enum\ContactType;
use App\Enum\CustomerTypeEnum;
use App\Enum\GenderEnum;
use App\Enum\VAT;
use App\Integrations\Pipeliner\Enum\CloudObjectTypeEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\ContactRelationEntity;
use App\Integrations\Pipeliner\Models\CreateAccountInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateOrUpdateContactAccountRelationInput;
use App\Integrations\Pipeliner\Models\CreateOrUpdateContactAccountRelationInputCollection;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\UpdateAccountInput;
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\SalesUnit;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Address\AddressHashResolver;
use App\Services\Address\ImportedAddressToAddressProjector;
use App\Services\Contact\ContactHashResolver;
use App\Services\Contact\ImportedContactToContactProjector;
use App\Services\Pipeliner\PipelinerClientEntityToUserProjector;
use App\Services\Pipeliner\CachedDataEntityResolver;
use App\Services\Pipeliner\CachedFieldApiNameResolver;
use App\Services\Pipeliner\CachedFieldEntityResolver;
use App\Services\ThumbHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class CompanyDataMapper
{
    public function __construct(protected CachedFieldApiNameResolver $fieldApiNameResolver,
                                protected CachedFieldEntityResolver  $pipelinerFieldResolver,
                                protected CachedDataEntityResolver   $pipelinerDataResolver,
                                protected ImportedAddressToAddressProjector $addressProjector,
                                protected ImportedContactToContactProjector $contactProjector)
    {
    }

    public function mapPipelinerCreateAccountInput(Company $company): CreateAccountInput
    {
        /** @var Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->first(static fn(Address $address): bool => AddressType::INVOICE === $address->address_type);

        $picture = InputValueEnum::Miss;

        if (null !== $company->image) {
            $picture = $this->mapCreateCloudObjectInputFromCompanyImage($company);
        }

        $documents = $company
            ->attachments
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

        return new CreateAccountInput(
            name: $company->name,
            ownerId: (string)$company->owner?->pl_reference,
            address: (string)$defaultAddress?->address_1,
            city: (string)$defaultAddress?->city,
            country: (string)$defaultAddress?->country?->name,
            customerTypeId: isset($company->customer_type)
                ? $this->resolveDataEntityByOptionName('Account', 'customer_type_id', $company->customer_type->value)?->id ?? InputValueEnum::Miss
                : InputValueEnum::Miss,
            customFields: json_encode($this->projectCompanyAttrsToCustomFields($company)),
            email1: (string)$company->email,
            phone1: (string)$company->phone,
            homePage: (string)$company->website,
            stateProvince: (string)$defaultAddress?->state,
            unitId: $company->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            zipCode: (string)$defaultAddress?->post_code,
            picture: $picture,
            documents: $documents
        );
    }

    public function mapPipelinerUpdateAccountInput(Company $company, AccountEntity $accountEntity): UpdateAccountInput
    {
        /** @var Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->first(static fn(Address $address): bool => AddressType::INVOICE === $address->address_type);

        $oldFields = [
            'name' => $accountEntity->formattedName,
            'address' => $accountEntity->address,
            'city' => $accountEntity->city,
            'country' => $accountEntity->country,
            'email1' => $accountEntity->email1,
            'phone1' => $accountEntity->phone1,
            'homePage' => $accountEntity->homePage,
            'stateProvince' => $accountEntity->stateProvince,
            'zipCode' => $accountEntity->zipCode,
            'unitId' => $accountEntity->unit?->id,
            'customerTypeId' => $accountEntity->customerType?->id,
            'customFields' => json_encode($accountEntity->customFields),
        ];

        $newFields = [
            'name' => $company->name,
            'address' => (string)$defaultAddress?->address_1,
            'city' => (string)$defaultAddress?->city,
            'country' => (string)$defaultAddress?->country?->name,
            'email1' => (string)$company->email,
            'phone1' => (string)$company->phone,
            'homePage' => (string)$company->website,
            'stateProvince' => (string)$defaultAddress?->state,
            'zipCode' => (string)$defaultAddress?->post_code,
            'unitId' => $company->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            'customerTypeId' => isset($company->customer_type)
                ? $this->resolveDataEntityByOptionName('Account', 'customer_type_id', $company->customer_type->value)?->id ?? InputValueEnum::Miss
                : InputValueEnum::Miss,
            'customFields' => json_encode(array_merge($accountEntity->customFields, $this->projectCompanyAttrsToCustomFields($company))),
            'documents' => $company
                ->attachments
                ->whereNotNull('pl_reference')
                ->values()
                ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                    return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
                })
                ->whenNotEmpty(
                    static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                        return new CreateCloudObjectRelationInputCollection(...$collection->all());
                    },
                    static fn(): InputValueEnum => InputValueEnum::Miss),
        ];

        $changedFields = array_udiff_assoc($newFields, $oldFields, function (mixed $a, mixed $b): int {
            if ($a === null || $b === null) {
                return $a === $b ? 0 : 1;
            }

            if ($a === InputValueEnum::Miss || $b === InputValueEnum::Miss) {
                return $a === $b ? 0 : 1;
            }

            return $a <=> $b;
        });

        if (null !== $company->image) {
            $picture = $this->mapCreateCloudObjectInputFromCompanyImage($company);

            if ($picture->filename !== $accountEntity->picture?->filename) {
                $changedFields['picture'] = $picture;
            }
        }

        return new UpdateAccountInput(
            $accountEntity->id,
            ...$changedFields
        );
    }

    public function mapPipelinerCreateOrUpdateContactAccountRelationInputCollection(Company $company): CreateOrUpdateContactAccountRelationInputCollection
    {
        $primaryContactMatched = false;

        return $company->contacts
            ->map(static function (Contact $contact) use (
                $company,
                &$primaryContactMatched
            ): CreateOrUpdateContactAccountRelationInput {
                $input = new CreateOrUpdateContactAccountRelationInput(
                    accountId: $company->pl_reference,
                    contactId: $contact->pl_reference,
                    isPrimary: false === $primaryContactMatched && (bool)$contact->pivot->is_default
                );

                $primaryContactMatched = $primaryContactMatched || (bool)$contact->pivot->is_default;

                return $input;
            })
            ->pipe(static function (BaseCollection $collection) {
                return new CreateOrUpdateContactAccountRelationInputCollection(...$collection->all());
            });
    }

    public function projectCompanyAttrsToCustomFields(Company $company): array
    {
        $sortedDefaultAddresses = $company->addresses->sortByDesc('pivot.is_default');

        /** @var Address|null $invoiceAddress */
        $invoiceAddress = $sortedDefaultAddresses->first(static function (Address $address): bool {
            return AddressType::INVOICE === $address->address_type;
        });

        /** @var Address|null $hwAddress */
        $hwAddress = $sortedDefaultAddresses->first(static function (Address $address): bool {
            return AddressType::HARDWARE === $address->address_type;
        });

        /** @var Address|null $swAddress */
        $swAddress = $sortedDefaultAddresses->first(static function (Address $address): bool {
            return AddressType::SOFTWARE === $address->address_type;
        });

        $vendorsCustomFields = $this->projectVendorsToCustomFields(...$company->vendors);

        $customFields = [...$vendorsCustomFields];

        if (null !== $invoiceAddress) {
            $customFields['cfAddress2n'] = $invoiceAddress?->address_2 ?? '';
        }

        if (null !== $swAddress) {
            $customFields['cfSoftwareCountry'] = $swAddress?->country?->iso_3166_2 ?? '';
        }

        if (null !== $hwAddress) {
            $customFields['cfHardwareCountry'] = $hwAddress?->country?->iso_3166_2 ?? '';
        }

        if (null !== $invoiceAddress) {
            $customFields['cfStateCode'] = $invoiceAddress?->state_code ?? '';
        }

        $customFields['cfVat'] = VAT::VAT_NUMBER === $company->vat_type ? $company->vat : '';
        $customFields['cfVatTypeId'] = $this->resolveDataEntityByOptionName(
            entityName: 'Account',
            apiName: 'cf_vat_type_id',
            optionName: match ($company->vat_type ?? VAT::NO_VAT) {
                VAT::NO_VAT => 'NO VAT',
                VAT::EXEMPT => 'Exempt',
                VAT::VAT_NUMBER => 'Vat Number',
            }
        )?->id ?? '';

        $categoryFieldMap = $this->getCategoryCustomFieldMap();

        foreach ($categoryFieldMap as $category => $field) {
            /** @var CompanyCategoryEnum $category */
            $customFields[$field] = $company->categories->containsStrict('name', $category->value);
        }

        return $customFields;
    }

    private function getCategoryCustomFieldMap(): \WeakMap
    {
        return tap(new \WeakMap(), static function (\WeakMap $map): void {
            $map[CompanyCategoryEnum::BusinessPartner] = 'cfBusinessPartner';
            $map[CompanyCategoryEnum::Reseller] = 'cfReseller';
            $map[CompanyCategoryEnum::EndUser] = 'cfEndUser';
            $map[CompanyCategoryEnum::Distributor] = 'cfDistributor';
        });
    }

    public function resolveCategoriesFromCustomFields(array $customFields): BaseCollection
    {
        $map = $this->getCategoryCustomFieldMap();

        $categories = collect();

        foreach ($map as $category => $field) {
            if (Arr::get($customFields, $field)) {
                $categories->push($category);
            }
        }

        return $categories;
    }

    public function mapImportedCompanyFromAccountEntity(AccountEntity $entity, array $contactRelations): ImportedCompany
    {
        return tap(new ImportedCompany(), function (ImportedCompany $account) use ($contactRelations, $entity): void {
            $account->{$account->getKeyName()} = (string)Uuid::generate(4);
            $account->pl_reference = $entity->id;
            $account->company_name = $entity->formattedName;
            $account->company_categories = $this->resolveCategoriesFromCustomFields($entity->customFields);
            $account->customer_type = CustomerTypeEnum::tryFrom($entity->customerType?->optionName ?? '');
            $account->email = $entity?->email1;
            $account->phone = $entity?->phone1;
            $account->website = $entity->homePage;
            $account->vat_type = $this->resolveVatTypeFromCustomFields($entity->customFields);
            $account->vat = $entity->customFields['cfVat'] ?? null;
            $account->vendors_cs = $this->resolveVendorsCsFromCustomFields($entity->customFields);
            $account->address_1 = $entity->address;
            $account->address_2 = Arr::get($entity->customFields, 'cfAddress2n');
            $account->city = $entity->city;
            $account->post_code = $entity->zipCode;
            $account->state = $entity->stateProvince;
            $account->state_code = Arr::get($entity->customFields, 'cfStateCode');
            $account->country_name = $entity->country;
            $account->hw_country_code = Arr::get($entity->customFields, 'cfHardwareCountry');
            $account->sw_country_code = Arr::get($entity->customFields, 'cfSoftwareCountry');
            $account->picture_filename = $entity->picture?->filename;
            $account->picture_url = $entity->picture?->url;

            if (null !== $entity->unit) {
                $account->salesUnit()->associate(
                    SalesUnit::query()->where('unit_name', $entity->unit->name)->first()
                );
            }

            if ($entity->customFields['cfReseller'] ?? false) {
                $account->flags |= ImportedCompany::IS_RESELLER;
            }

            if ($entity->customFields['cfEndUser'] ?? false) {
                $account->flags |= ImportedCompany::IS_END_USER;
            }

            [$addresses,
                $contacts] = $this->mapImportedAddressesContactsFromContactRelations(...$contactRelations);

            $account->setRelation('addresses', $addresses);
            $account->setRelation('contacts', $contacts);

            $primaryContact = $account->contacts->first(static fn(ImportedContact $contact) => $contact->is_primary);

            $account->primaryContact()->associate($primaryContact);
        });
    }

    public function mergeAttributesFrom(Company $company, ImportedCompany $another): void
    {
        $company->vat_type = coalesce_blank($another->vat_type, $company->vat_type, VAT::NO_VAT);

        if (VAT::NO_VAT !== $company->vat_type) {
            $company->vat = coalesce_blank($another->vat, $company->vat);
        }

        $company->name = $another->company_name;
        $company->email = coalesce_blank($another->email, $company->email);
        $company->phone = coalesce_blank($another->phone, $company->phone);
        $company->website = coalesce_blank($another->website, $company->website);
        $company->customer_type = $another->customer_type;

        $vendorNames = Str::of($another->vendors_cs)
            ->explode(',')
            ->filter(static fn(string $v) => filled($v))
            ->map(static function (string $v) {
                return match ($v = trim($v)) {
                    'HPE' => 'Hewlett Packard Enterprise',
                    default => $v,
                };
            })
            ->values()
            ->all();

        $company->setRelation(
            'vendors',
            Vendor::query()
                ->whereIn('name', $vendorNames)
                ->get()
        );

        $company->setRelation(
            'categories',
            CompanyCategory::query()
                ->whereIn('name', $another->company_categories)
                ->get()
        );

        if (null !== $another->salesUnit) {
            $company->salesUnit()->associate($another->salesUnit);
        }

        /** @var $importedAddressesHaveRelThroughPlRef Collection */
        /** @var $importedAddressesDontHaveRelThroughPlRef Collection */
        [$importedAddressesHaveRelThroughPlRef, $importedAddressesDontHaveRelThroughPlRef] =
            $another->addresses
                ->partition(static function (ImportedAddress $importedAddress) use ($company): bool {
                    return $company->addresses->containsStrict('pl_reference', $importedAddress->pl_reference);
                });

        $importedAddressesHaveRelThroughPlRef->each(static function (ImportedAddress $importedAddress) use ($company) {
            $company->addresses->whereStrict('pl_reference', $importedAddress->pl_reference)
                ->each(static function (Address $address) use ($importedAddress) {
                    $address->address_type = $importedAddress->address_type;
                    $address->address_1 = $importedAddress->address_1;
                    $address->address_2 = $importedAddress->address_2;
                    $address->city = $importedAddress->city;
                    $address->post_code = $importedAddress->post_code;
                    $address->state = $importedAddress->state;
                    $address->state_code = $importedAddress->state_code;
                    $address->country()->associate($importedAddress->country()->getParentKey());
                });
        });

        /** @var $importedContactsHaveRelThroughPlRef Collection */
        /** @var $importedContactsDontHaveRelThroughPlRef Collection */
        [$importedContactsHaveRelThroughPlRef, $importedContactsDontHaveRelThroughPlRef] =
            $another->contacts
                ->partition(static function (ImportedContact $importedAddress) use ($company): bool {
                    return $company->contacts->containsStrict('pl_reference', $importedAddress->pl_reference);
                });

        $importedContactsHaveRelThroughPlRef->each(static function (ImportedContact $importedContact) use ($company) {
            $company->contacts->whereStrict('pl_reference', $importedContact->pl_reference)
                ->each(static function (Contact $contact) use ($importedContact) {
                    $contact->contact_type = $importedContact->contact_type;
                    $contact->salesUnit()->associate($importedContact->salesUnit ?? $contact->salesUnit);
                    $contact->gender = $importedContact->gender;
                    $contact->first_name = $importedContact->first_name;
                    $contact->last_name = $importedContact->last_name;
                    $contact->email = $importedContact->email;
                    $contact->phone = $importedContact->phone;
                    $contact->mobile = $importedContact->phone_2;
                    $contact->job_title = $importedContact->job_title;
                    $contact->contact_name = $importedContact->contact_name;
                });
        });

        $addressHashResolver = new AddressHashResolver();
        $contactHashResolver = new ContactHashResolver();

        $currentAddressMap = $company->addresses->keyBy($addressHashResolver);
        $currentContactMap = $company->contacts->keyBy($contactHashResolver);

        $anotherAddressMap = $importedAddressesDontHaveRelThroughPlRef->keyBy($addressHashResolver);
        $anotherContactMap = $importedContactsDontHaveRelThroughPlRef->keyBy($contactHashResolver);

        $newImportedAddresses = $anotherAddressMap->diffKeys($currentAddressMap);
        $newImportedContacts = $anotherContactMap->diffKeys($currentContactMap);

        $newAddressMap = $newImportedAddresses
            ->mapWithKeys(function (ImportedAddress $a) use ($company): array {
                $address = ($this->addressProjector)($a);
                $address->user()->associate($address->user ?? $company->user);

                return [$a->getKey() => $address];
            });

        $newContactMap = $newImportedContacts
            ->mapWithKeys(function (ImportedContact $c) use ($newAddressMap, $company): array {
                $contact = ($this->contactProjector)($c, $newAddressMap->get($c->address()->getParentKey()));
                $contact->user()->associate($contact->user ?? $company->user);

                return [$c->getKey() => $contact];
            });

        $newContactCollection = Collection::make($newContactMap->values())->reject(static fn(Contact $contact): bool => $contact->isEmpty());
        $newAddressCollection = Collection::make($newAddressMap->values())->reject(static fn(Address $address): bool => $address->isEmpty());

        $company->contacts->push(...$newContactCollection);
        $company->addresses->push(...$newAddressCollection);

        $defaultInvoiceAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->firstWhere('address_type', '===', AddressType::INVOICE);

        if (null === $defaultInvoiceAddress) {
            $company->addresses->push($defaultInvoiceAddress = tap(new Address(), static function (Address $address): void {
                $address->{$address->getKeyName()} = (string)Uuid::generate(4);
            }));
        }

        tap($defaultInvoiceAddress, static function (Address $address) use ($company, $another): void {
            $address->setRelation($company->addresses()->getPivotAccessor(), $company->addresses()->newPivot([
                'is_default' => true,
                $company->addresses()->getRelatedPivotKeyName() => $address->getKey(),
                $company->addresses()->getForeignPivotKeyName() => $company->getKey(),
                $company->addresses()->getMorphType() => $company->getMorphClass(),
            ], $address->exists));

            $address->address_type = AddressType::INVOICE;
            $address->address_1 = $another->address_1;
            $address->address_2 = $another->address_2;
            $address->city = $another->city;
            $address->post_code = $another->post_code;
            $address->state = $another->state;
            $address->state_code = $another->state_code;
            $address->country()->associate(
                Country::query()->where('name', $another->country_name)->first()
            );
        });

        // Change attributes of the addresses associated with contacts
        $company->contacts->each(static function (Contact $contact): void {
            if (null === $contact->address) {
                return;
            }

            tap($contact->address, static function (Address $address) use ($contact) {
                $address->address_type = $contact->contact_type;
            });
        });

        $hwSwCountryMap = [
            AddressType::HARDWARE => isset($another->hw_country_code)
                ? Country::query()->where('iso_3166_2', $another->hw_country_code)->first()
                : null,
            AddressType::SOFTWARE => isset($another->sw_country_code)
                ? Country::query()->where('iso_3166_2', $another->sw_country_code)->first()
                : null,
        ];

        // Update HW & SW countries
        $company->addresses
            ->each(static function (Address $address) use ($hwSwCountryMap): void {
                $country = match ($address->address_type) {
                    AddressType::HARDWARE => $hwSwCountryMap[AddressType::HARDWARE],
                    AddressType::SOFTWARE => $hwSwCountryMap[AddressType::SOFTWARE],
                    default => $address->country,
                };

                if ($address->pivot?->is_default) {
                    $address->country()->associate($country ?? $address->country);
                }
            });
    }

    private function mapImportedAddressesContactsFromContactRelations(ContactRelationEntity ...$entities): array
    {
        $newAddresses = new Collection();
        $newContacts = new Collection();

        foreach ($entities as $contactRelation) {
            $owner = PipelinerClientEntityToUserProjector::from($contactRelation->contact->owner)();

            $address = $this->mapImportedAddressFromContactRelationEntity($contactRelation, $owner);
            $contact = $this->mapImportedContactFromContactRelationEntity($contactRelation, $owner);

            $newAddresses[] = $address;
            $newContacts[] = $contact->address()->associate($address->getKey());
        }

        return [$newAddresses, $newContacts];
    }

    private function mapImportedAddressFromContactRelationEntity(ContactRelationEntity $entity,
                                                                 User                  $owner): ImportedAddress
    {
        return tap(new ImportedAddress(), function (ImportedAddress $address) use (
            $entity,
            $owner
        ): void {
            $address->{$address->getKeyName()} = (string)Uuid::generate(4);
            $address->pl_reference = $entity->contact->id;
            $address->owner()->associate($owner);

            $type = ($this->pipelinerDataResolver)($entity->contact->customFields['cfType1Id'] ?? null)?->optionName ?? '';
            $address->address_type = match (trim(strtolower($type))) {
                'hardware' => AddressType::HARDWARE,
                'software' => AddressType::SOFTWARE,
                default => AddressType::INVOICE
            };

            $address->address_1 = $entity->contact->address;
            $address->address_2 = $entity->contact->customFields['cfAddressTwo'] ?? null;

            $address->city = $entity->contact->city;
            $address->post_code = $entity->contact->zipCode;
            $address->state = $entity->contact->stateProvince;
            $address->state_code = $entity->contact->customFields['cfStateCode1'] ?? null;

            if (filled($entity->contact->country)) {
                $address->country()->associate(
                    Country::query()->where('name', $entity->contact->country)->first()
                );
            } else {
                $address->country()->disassociate();
            }
        });
    }

    private function mapImportedContactFromContactRelationEntity(ContactRelationEntity $entity,
                                                                 User                  $owner): ImportedContact
    {
        return tap(new ImportedContact(), function (ImportedContact $contact) use ($entity, $owner): void {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
            $contact->pl_reference = $entity->contact->id;
            $contact->owner()->associate($owner);

            if (null !== $entity->contact->unit) {
                $contact->salesUnit()->associate(
                    SalesUnit::query()->where('unit_name', $entity->contact->unit->name)->first()
                );
            }

            $type = ($this->pipelinerDataResolver)($entity->contact->customFields['cfType1Id'] ?? null)?->optionName ?? '';
            $contact->contact_type = match (trim(strtolower($type))) {
                'software' => ContactType::SOFTWARE,
                default => ContactType::HARDWARE
            };

            $contact->gender = GenderEnum::tryFrom($entity->contact->gender->name) ?? GenderEnum::Unknown;
            $contact->first_name = $entity->contact->firstName;
            $contact->last_name = $entity->contact->lastName;
            $contact->email = $entity->contact->email1;
            $contact->phone = $entity->contact->phone1;
            $contact->phone_2 = $entity->contact->phone2;
            $contact->job_title = Arr::get($entity->contact->customFields, 'cfJobTitle');
            $contact->is_verified = false;

            $contact->is_primary = $entity->isPrimary;
        });
    }

    public function resolveVatTypeFromCustomFields(array $customFields): string
    {
        $fieldValue = ($this->pipelinerDataResolver)($customFields['cfVatTypeId'] ?? null)?->optionName;

        $vatType = trim(strtolower($fieldValue ?? ''));

        return match ($vatType) {
            'vat number' => VAT::VAT_NUMBER,
            'exempt' => VAT::EXEMPT,
            default => VAT::NO_VAT,
        };
    }

    public function mapCreateCloudObjectInputFromCompanyImage(Company $company): CreateCloudObjectInput
    {
        $thumbnails = ThumbHelper::getLogoDimensionsFromImage(
            $company->image,
            $company->thumbnailProperties(),
            flags: ThumbHelper::MAP | ThumbHelper::ABS_PATH
        );

        $content = base64_encode(file_get_contents($thumbnails['x3']));

        return new CreateCloudObjectInput(
            filename: pathinfo($thumbnails['x3'], PATHINFO_BASENAME),
            type: CloudObjectTypeEnum::S3Image,
            content: $content
        );
    }

    public function resolveVendorsCsFromCustomFields(array $customFields)
    {
        $fieldIds = $customFields['cfVendor2'] ?? [];

        if (empty($fieldIds)) {
            return null;
        }

        return collect($fieldIds)
            ->map(fn(string $id) => ($this->pipelinerDataResolver)($id)?->optionName)
            ->implode(', ');
    }

    public function projectVendorsToCustomFields(Vendor ...$vendors): array
    {
        $mapping = config('pipeliner.custom_fields.vendor_code_option_name', []);

        $cfVendor = Collection::make($vendors)->map(function (Vendor $vendor) use ($mapping): ?string {
            $optionName = (string)($mapping[$vendor->short_code] ?? $vendor->short_code);

            $dataEntity = $this->resolveDataEntityByOptionName(entityName: 'Account', apiName: 'cf_vendor2', optionName: $optionName);

            return $dataEntity?->id;
        })
            ->filter('filled')
            ->values()
            ->all();

        $customFields = collect([
            'cfVendor2' => $cfVendor,
        ]);

        return $customFields->mapWithKeys(function (mixed $value, string $reference): array {
            $key = ($this->fieldApiNameResolver)('Account', $reference);

            return [$key => $value];
        })
            ->all();
    }

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
}