<?php

namespace App\Domain\Company\Services;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Address\Models\ImportedAddress;
use App\Domain\Address\Services\AddressHashResolver;
use App\Domain\Address\Services\ImportedAddressToAddressProjector;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Enum\CompanyCategoryEnum;
use App\Domain\Company\Enum\CustomerTypeEnum;
use App\Domain\Company\Enum\VAT;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyCategory;
use App\Domain\Company\Models\ImportedCompany;
use App\Domain\Company\Services\Exceptions\CompanyDataMappingException;
use App\Domain\Contact\Enum\ContactType;
use App\Domain\Contact\Enum\GenderEnum;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ImportedContact;
use App\Domain\Contact\Services\ContactHashResolver;
use App\Domain\Contact\Services\ImportedContactToContactProjector;
use App\Domain\Country\Models\Country;
use App\Domain\Language\Models\Language;
use App\Domain\Pipeliner\Integration\CustomFieldName;
use App\Domain\Pipeliner\Integration\Enum\CloudObjectTypeEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\SharingRoleEnum;
use App\Domain\Pipeliner\Integration\Models\AccountEntity;
use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationEntity;
use App\Domain\Pipeliner\Integration\Models\ContactRelationEntity;
use App\Domain\Pipeliner\Integration\Models\CreateAccountInput;
use App\Domain\Pipeliner\Integration\Models\CreateAccountSharingClientRelationInput;
use App\Domain\Pipeliner\Integration\Models\CreateAccountSharingClientRelationInputCollection;
use App\Domain\Pipeliner\Integration\Models\CreateCloudObjectInput;
use App\Domain\Pipeliner\Integration\Models\CreateCloudObjectRelationInput;
use App\Domain\Pipeliner\Integration\Models\CreateCloudObjectRelationInputCollection;
use App\Domain\Pipeliner\Integration\Models\CreateOrUpdateContactAccountRelationInput;
use App\Domain\Pipeliner\Integration\Models\CreateOrUpdateContactAccountRelationInputCollection;
use App\Domain\Pipeliner\Integration\Models\DataEntity;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\FieldFilterInput;
use App\Domain\Pipeliner\Integration\Models\UpdateAccountInput;
use App\Domain\Pipeliner\Services\CachedDataEntityResolver;
use App\Domain\Pipeliner\Services\CachedFieldApiNameResolver;
use App\Domain\Pipeliner\Services\CachedFieldEntityResolver;
use App\Domain\Pipeliner\Services\PipelinerClientEntityToUserProjector;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class CompanyDataMapper
{
    public function __construct(
        protected CachedFieldApiNameResolver $fieldApiNameResolver,
        protected CachedFieldEntityResolver $pipelinerFieldResolver,
        protected CachedDataEntityResolver $pipelinerDataResolver,
        protected ImportedAddressToAddressProjector $addressProjector,
        protected ImportedContactToContactProjector $contactProjector,
        protected PipelinerClientEntityToUserProjector $clientProjector,
    ) {
    }

    /**
     * @throws CompanyDataMappingException
     */
    public function mapPipelinerCreateAccountInput(Company $company): CreateAccountInput
    {
        /** @var \App\Domain\Address\Models\Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->lazy()
            ->filter(static fn (Address $address): bool => (bool) $address->pivot?->is_default)
            ->first(static fn (Address $address): bool => AddressType::INVOICE === $address->address_type);

        $picture = InputValueEnum::Miss;

        if (null !== $company->image) {
            $picture = $this->mapCreateCloudObjectInputFromCompanyImage($company);
        }

        $documents = $company
            ->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(static function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn (): InputValueEnum => InputValueEnum::Miss);

        $sharingClients = $this->mapPipelinerCreateAccountSharingClientRelationInputCollection($company);

        $customerTypeId = InputValueEnum::Miss;
        if (isset($company->customer_type)) {
            $customerTypeId = $this->resolveDataEntityByOptionName(
                entityName: 'Account',
                apiName: 'customer_type_id',
                optionName: $company->customer_type->value
            )?->id;
            $customerTypeId ??= InputValueEnum::Miss;
        }

        $companyFields = [
            'name' => $company->name,
            'ownerId' => (string) $company->owner?->pl_reference,
            'customerTypeId' => $customerTypeId,
            'customFields' => json_encode($this->projectCompanyAttrsToCustomFields($company)),
            'email1' => (string) $company->email,
            'phone1' => (string) $company->phone,
            'homePage' => (string) $company->website,
            'unitId' => $company->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            'picture' => $picture,
            'documents' => $documents,
            'sharingClients' => $sharingClients,
        ];

        $addressFields = $this->mapDefaultAddressFields($defaultAddress);

        return new CreateAccountInput(
            ...array_merge($companyFields, $addressFields),
        );
    }

    /**
     * @param list<AccountSharingClientRelationEntity> $sharingClients
     *
     * @throws CompanyDataMappingException
     */
    public function mapPipelinerUpdateAccountInput(
        Company $company,
        AccountEntity $accountEntity,
        array $sharingClients
    ): UpdateAccountInput {
        /** @var \App\Domain\Address\Models\Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->lazy()
            ->filter(static fn (Address $address): bool => (bool) $address->pivot?->is_default)
            ->first(static fn (Address $address): bool => AddressType::INVOICE === $address->address_type);

        $oldFields = [
            'ownerId' => $accountEntity->owner?->id,
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
            'ownerId' => $company->owner?->pl_reference ?? InputValueEnum::Miss,
            'name' => $company->name,
            'email1' => (string) $company->email,
            'phone1' => (string) $company->phone,
            'homePage' => (string) $company->website,
            'unitId' => $company->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            'customerTypeId' => isset($company->customer_type)
                ? $this->resolveDataEntityByOptionName('Account', 'customer_type_id', $company->customer_type->value)?->id ?? InputValueEnum::Miss
                : InputValueEnum::Miss,
            'customFields' => json_encode(array_merge($accountEntity->customFields, $this->projectCompanyAttrsToCustomFields($company))),
            'documents' => $company
                ->attachments
                ->whereNotNull('pl_reference')
                ->values()
                ->map(static function (Attachment $attachment): CreateCloudObjectRelationInput {
                    return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
                })
                ->whenNotEmpty(
                    static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                        return new CreateCloudObjectRelationInputCollection(...$collection->all());
                    },
                    static fn (): InputValueEnum => InputValueEnum::Miss),
            'sharingClients' => $this->mapPipelinerCreateAccountSharingClientRelationInputCollection(
                $company,
                $sharingClients
            ),
        ];

        $newAddressFields = $this->mapDefaultAddressFields($defaultAddress);

        $newFields = array_merge($newFields, $newAddressFields);

        $changedFields = array_udiff_assoc($newFields, $oldFields, static function (mixed $a, mixed $b): int {
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

    private function mapDefaultAddressFields(?Address $address): array
    {
        if ($address) {
            return [
                'address' => (string) $address?->address_1,
                'city' => (string) $address?->city,
                'country' => (string) $address?->country?->name,
                'stateProvince' => (string) $address?->state,
                'zipCode' => (string) $address?->post_code,
            ];
        }

        return [
            'address' => InputValueEnum::Miss,
            'city' => InputValueEnum::Miss,
            'country' => InputValueEnum::Miss,
            'stateProvince' => InputValueEnum::Miss,
            'zipCode' => InputValueEnum::Miss,
        ];
    }

    /**
     * @param list<AccountSharingClientRelationEntity> $sharingClients
     */
    private function mapPipelinerCreateAccountSharingClientRelationInputCollection(
        Company $company,
        array $sharingClients = [],
    ): CreateAccountSharingClientRelationInputCollection {
        $editors = $company
            ->sharingUsers
            ->lazy()
            ->whereNotNull('pl_reference')
            ->pluck('pl_reference')
            ->unique()
            ->values()
            ->map(static function (string $ref): CreateAccountSharingClientRelationInput {
                return new CreateAccountSharingClientRelationInput(
                    clientId: $ref,
                    role: SharingRoleEnum::Editor,
                );
            })
            ->collect();

        $otherSharingClients = collect($sharingClients)
            ->lazy()
            ->reject(static function (AccountSharingClientRelationEntity $entity): bool {
                return $entity->role === SharingRoleEnum::Editor;
            })
            ->values()
            ->map(static function (AccountSharingClientRelationEntity $entity
            ): CreateAccountSharingClientRelationInput {
                return new CreateAccountSharingClientRelationInput(
                    clientId: $entity->client->id,
                    role: $entity->role,
                );
            })
            ->collect();

        return $editors->merge($otherSharingClients)
            ->pipe(static function (BaseCollection $collection): CreateAccountSharingClientRelationInputCollection {
                return new CreateAccountSharingClientRelationInputCollection(...$collection->all());
            });
    }

    public function mapPipelinerCreateOrUpdateContactAccountRelationInputCollection(Company $company
    ): CreateOrUpdateContactAccountRelationInputCollection {
        $primaryContactMatched = false;

        return $company->contacts
            ->map(static function (Contact $contact) use (
                $company,
                &$primaryContactMatched
            ): CreateOrUpdateContactAccountRelationInput {
                $input = new CreateOrUpdateContactAccountRelationInput(
                    accountId: $company->pl_reference,
                    contactId: $contact->pl_reference,
                    isPrimary: false === $primaryContactMatched && (bool) $contact->pivot->is_default
                );

                $primaryContactMatched = $primaryContactMatched || (bool) $contact->pivot->is_default;

                return $input;
            })
            ->pipe(static function (BaseCollection $collection) {
                return new CreateOrUpdateContactAccountRelationInputCollection(...$collection->all());
            });
    }

    public function projectCompanyAttrsToCustomFields(Company $company): array
    {
        $sortedDefaultAddresses = $company->addresses->sortByDesc('pivot.is_default');

        /** @var \App\Domain\Address\Models\Address|null $invoiceAddress */
        $invoiceAddress = $sortedDefaultAddresses->first(static function (Address $address): bool {
            return AddressType::INVOICE === $address->address_type;
        });

        /** @var Address|null $hwAddress */
        $hwAddress = $sortedDefaultAddresses->first(static function (Address $address): bool {
            return AddressType::HARDWARE === $address->address_type;
        });

        /** @var \App\Domain\Address\Models\Address|null $swAddress */
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
            /* @var \App\Domain\Company\Enum\CompanyCategoryEnum $category */
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

    /**
     * @param array<string, ContactRelationEntity>     $contactRelations
     * @param list<AccountSharingClientRelationEntity> $sharingClients
     *
     * @throws \Exception
     */
    public function mapImportedCompanyFromAccountEntity(
        AccountEntity $entity,
        array $contactRelations,
        array $sharingClients
    ): ImportedCompany {
        return tap(new ImportedCompany(),
            function (ImportedCompany $account) use ($sharingClients, $contactRelations, $entity): void {
                $account->{$account->getKeyName()} = (string) Uuid::generate(4);
                $account->pl_reference = $entity->id;
                if ($entity->owner !== null) {
                    $account->owner()->associate(($this->clientProjector)($entity->owner));
                }
                $account->company_name = $entity->formattedName;
                $account->company_categories = $this->resolveCategoriesFromCustomFields($entity->customFields);
                $account->customer_type = CustomerTypeEnum::tryFrom($entity->customerType?->optionName ?? '');
                $account->email = $entity?->email1;
                $account->phone = $entity?->phone1;
                $account->website = $entity->homePage;
                $account->vat_type = $this->resolveVatTypeFromCustomFields($entity->customFields);
                $account->vat = $entity->customFields['cfVat'] ?? null;
                $account->vendors_cs = $this->resolveVendorsCsFromCustomFields($entity->customFields);
                $account->contact_relations = collect($contactRelations)->map(
                    static fn (ContactRelationEntity $rel): array => [
                        'id' => $rel->id,
                        'is_primary' => $rel->isPrimary,
                        'contact_id' => $rel->contact->id,
                    ])
                    ->values();
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

                $account->flags |= ImportedCompany::COMPANY_DATA_EXISTS;

                [$addresses, $contacts] = $this->mapImportedAddressesContactsFromContactRelations(...$contactRelations);

                $account->setRelation('addresses', $addresses);
                $account->setRelation('contacts', $contacts);
                $account->setRelation('sharingUsers', $this->mapUsersFromSharingClientRelations(...$sharingClients));

                $primaryContact = $account->contacts->first(static fn (ImportedContact $contact
                ) => $contact->is_primary);

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

        if ($another->owner !== null) {
            $company->owner()->associate($another->owner);
        }

        $company->setRelation('sharingUsers', $another->sharingUsers);

        $vendorNames = Str::of($another->vendors_cs)
            ->explode(',')
            ->filter(static fn (string $v) => filled($v))
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

        $importedAddressesHaveRelThroughPlRef->each(static function (ImportedAddress $importedAddress) use ($company
        ): void {
            $company->addresses->whereStrict('pl_reference', $importedAddress->pl_reference)
                ->each(static function (Address $address) use ($importedAddress): void {
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

        $importedContactsHaveRelThroughPlRef->each(static function (ImportedContact $importedContact) use ($company
        ): void {
            $company->contacts->whereStrict('pl_reference', $importedContact->pl_reference)
                ->each(static function (Contact $contact) use ($importedContact): void {
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

                    if ($importedContact->language_name) {
                        $contact->language()->associate(
                            Language::query()
                                ->where('name', $importedContact->language_name)
                                ->first()
                        );
                    }
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

        $newContactCollection = Collection::make($newContactMap->values())->reject(static fn (Contact $contact): bool => $contact->isEmpty());
        $newAddressCollection = Collection::make($newAddressMap->values())->reject(static fn (Address $address): bool => $address->isEmpty());

        $company->contacts->push(...$newContactCollection);
        $company->addresses->push(...$newAddressCollection);

        $defaultInvoiceAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->firstWhere('address_type', '===', AddressType::INVOICE);

        if (null === $defaultInvoiceAddress) {
            $company->addresses->push($defaultInvoiceAddress = tap(new Address(), static function (Address $address): void {
                $address->{$address->getKeyName()} = (string) Uuid::generate(4);
            }));
        }

        tap($defaultInvoiceAddress, static function (Address $address) use ($company, $another): void {
            $pivot = $company->addresses()->newPivot([
                'is_default' => (bool) $address->pivot?->is_default,
                $company->addresses()->getRelatedPivotKeyName() => $address->getKey(),
                $company->addresses()->getForeignPivotKeyName() => $company->getKey(),
                $company->addresses()->getMorphType() => $company->getMorphClass(),
            ], $address->exists);

            $pivot->is_default = true;

            $address->setRelation($company->addresses()->getPivotAccessor(), $pivot);

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

    private function mapUsersFromSharingClientRelations(AccountSharingClientRelationEntity ...$entities): Collection
    {
        $users = Collection::empty();

        foreach ($entities as $entity) {
            if ($entity->role === SharingRoleEnum::Editor) {
                $users->push(($this->clientProjector)($entity->client));
            }
        }

        return $users;
    }

    private function mapImportedAddressesContactsFromContactRelations(ContactRelationEntity ...$entities): array
    {
        $newAddresses = new Collection();
        $newContacts = new Collection();

        foreach ($entities as $contactRelation) {
            $owner = ($this->clientProjector)($contactRelation->contact->owner);

            $address = $this->mapImportedAddressFromContactRelationEntity($contactRelation, $owner);
            $contact = $this->mapImportedContactFromContactRelationEntity($contactRelation, $owner);

            $newAddresses[] = $address;
            $newContacts[] = $contact->address()->associate($address->getKey());
        }

        return [$newAddresses, $newContacts];
    }

    private function mapImportedAddressFromContactRelationEntity(ContactRelationEntity $entity,
                                                                 User $owner): ImportedAddress
    {
        return tap(new ImportedAddress(), function (ImportedAddress $address) use (
            $entity,
            $owner
        ): void {
            $address->{$address->getKeyName()} = (string) Uuid::generate(4);
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
                                                                 User $owner): ImportedContact
    {
        return tap(new ImportedContact(), function (ImportedContact $contact) use ($entity, $owner): void {
            $contact->setId();
            $contact->pl_reference = $entity->contact->id;
            $contact->owner()->associate($owner);

            if (null !== $entity->contact->unit) {
                $contact->salesUnit()->associate(
                    SalesUnit::query()->where('unit_name', $entity->contact->unit->name)->first()
                );
            }

            $type = ($this->pipelinerDataResolver)($entity->contact->customFields[CustomFieldName::CONTACT_TYPE_ID] ?? null)?->optionName ?? '';
            $contact->contact_type = match (trim(strtolower($type))) {
                'software' => ContactType::SOFTWARE,
                default => ContactType::HARDWARE
            };

            $contact->language_name = ($this->pipelinerDataResolver)($entity->contact->customFields[CustomFieldName::CONTACT_LANGUAGE_ID] ?? null)?->optionName ?? '';

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
        $thumbnails = \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
            $company->image,
            $company->thumbnailProperties(),
            flags: \App\Domain\Image\Services\ThumbHelper::MAP | \App\Domain\Image\Services\ThumbHelper::ABS_PATH
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
            ->map(fn (string $id) => ($this->pipelinerDataResolver)($id)?->optionName)
            ->implode(', ');
    }

    public function projectVendorsToCustomFields(Vendor ...$vendors): array
    {
        $mapping = config('pipeliner.custom_fields.vendor_code_option_name', []);

        $cfVendor = Collection::make($vendors)->map(function (Vendor $vendor) use ($mapping): ?string {
            $optionName = (string) ($mapping[$vendor->short_code] ?? $vendor->short_code);

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

    public function cloneCompany(Company $company): Company
    {
        return tap(new Company(), static function (Company $oldCompany) use ($company): void {
            $oldCompany->setRawAttributes($company->getRawOriginal());
            $oldCompany->load(['addresses', 'contacts', 'vendors', 'aliases']);
        });
    }
}
