<?php

namespace App\Services\Company;

use App\Enum\AccountCategory;
use App\Enum\AddressType;
use App\Enum\ContactType;
use App\Enum\GenderEnum;
use App\Enum\VAT;
use App\Integrations\Pipeliner\Enum\CloudObjectTypeEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\ContactRelationEntity;
use App\Integrations\Pipeliner\Models\CreateAccountInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectInput;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\UpdateAccountInput;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Address\AddressHashResolver;
use App\Services\Address\ImportedAddressToAddressProjector;
use App\Services\Contact\ContactHashResolver;
use App\Services\Contact\ImportedContactToContactProjector;
use App\Services\Pipeliner\PipelinerClientEntityToUserProjector;
use App\Services\Pipeliner\RuntimeCachedDataEntityResolver;
use App\Services\Pipeliner\RuntimeCachedFieldApiNameResolver;
use App\Services\Pipeliner\RuntimeCachedFieldEntityResolver;
use App\Services\ThumbHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class CompanyDataMapper
{
    public function __construct(protected RuntimeCachedFieldApiNameResolver $fieldApiNameResolver,
                                protected RuntimeCachedFieldEntityResolver  $pipelinerFieldResolver,
                                protected RuntimeCachedDataEntityResolver   $pipelinerDataResolver,
                                protected ImportedAddressToAddressProjector $addressProjector,
                                protected ImportedContactToContactProjector $contactProjector)
    {
    }

    public function mapPipelinerCreateAccountInput(Company $company): CreateAccountInput
    {
        /** @var Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->first(static fn(Address $address): bool => ($address->address_type === AddressType::INVOICE));

        $picture = InputValueEnum::Miss;

        if (null !== $company->image) {
            $picture = $this->mapCreateCloudObjectInputFromCompanyImage($company);
        }

        return new CreateAccountInput(
            name: $company->name,
            ownerId: (string)$company->owner?->pl_reference,
            address: (string)$defaultAddress?->address_1,
            city: (string)$defaultAddress?->city,
            country: (string)$defaultAddress?->country?->name,
            customFields: json_encode($this->projectCompanyAttrsToCustomFields($company)),
            email1: (string)$company->email,
            phone1: (string)$company->phone,
            homePage: (string)$company->website,
            stateProvince: (string)$defaultAddress?->state,
            zipCode: (string)$defaultAddress?->post_code,
            picture: $picture
        );
    }

    public function mapPipelinerUpdateAccountInput(Company $company, AccountEntity $accountEntity): UpdateAccountInput
    {
        /** @var Address|null $defaultAddress */
        $defaultAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->first(static fn(Address $address): bool => ($address->address_type === AddressType::INVOICE));

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
            'customFields' => json_encode(array_merge($accountEntity->customFields, $this->projectCompanyAttrsToCustomFields($company))),
        ];

        $changedFields = array_diff_assoc($newFields, $oldFields);

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

        return $customFields;
    }

    public function mapImportedCompanyFromAccountEntity(AccountEntity $entity, array $contactRelations): ImportedCompany
    {
        return tap(new ImportedCompany(), function (ImportedCompany $account) use ($contactRelations, $entity): void {
            $account->{$account->getKeyName()} = (string)Uuid::generate(4);
            $account->pl_reference = $entity->id;
            $account->company_name = $entity->formattedName;
            $account->company_category = AccountCategory::RESELLER;
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
            $account->picture_filename = $entity->picture?->filename;
            $account->picture_url = $entity->picture?->url;

            if ($entity->customFields['cfReseller'] ?? false) {
                $account->flags |= ImportedCompany::IS_RESELLER;
            }

            if ($entity->customFields['cfEndUser'] ?? false) {
                $account->flags |= ImportedCompany::IS_END_USER;
            }

            [$addresses, $contacts] = $this->mapImportedAddressesContactsFromContactRelations(...$contactRelations);

            $account->setRelation('addresses', $addresses);
            $account->setRelation('contacts', $contacts);

            $primaryContact = $account->contacts->first(static fn(ImportedContact $contact) => $contact->is_primary);

            $account->primaryContact()->associate($primaryContact);
        });
    }

    public function mergeAttributesFrom(Company $company, ImportedCompany $another): void
    {
        if (is_null($company->vat_type)) {
            $company->vat_type = $another->vat_type ?? VAT::NO_VAT;
        }

        if (VAT::NO_VAT !== $company->vat_type) {
            $company->vat = coalesce_blank($company->vat, $another->vat);
        }

        $company->name = $another->company_name;
        $company->email = coalesce_blank($company->email, $another->email);
        $company->phone = coalesce_blank($company->phone, $another->phone);
        $company->website = coalesce_blank($company->website, $another->website);

        $vendorNames = Str::of($another->vendors_cs)
            ->explode(',')
            ->map(static fn(string $v) => trim($v))
            ->filter(static fn(string $v) => filled($v))
            ->values()
            ->all();

        $vendors = Vendor::query()
            ->whereIn('name', $vendorNames)
            ->get();

        $company->vendors->merge($vendors);

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

        $newContactMap = $newImportedContacts
            ->mapWithKeys(function (ImportedContact $c) use ($company): array {
                $contact = ($this->contactProjector)($c);
                $contact->user()->associate($contact->user ?? $company->user);

                return [$c->getKey() => $contact];
            });

        $newAddresses = $newImportedAddresses
            ->map(function (ImportedAddress $a) use ($company, $newContactMap): Address {
                $address = ($this->addressProjector)($a, $newContactMap->get($a->contact_id));
                $address->user()->associate($address->user ?? $company->user);

                return $address;
            })
            ->values();

        $newContactCollection = Collection::make($newContactMap->values())->reject(static fn(Contact $contact): bool => $contact->isEmpty());
        $newAddressCollection = Collection::make($newAddresses->values())->reject(static fn(Address $address): bool => $address->isEmpty());

        $company->contacts->push(...$newContactCollection);
        $company->addresses->push(...$newAddressCollection);
    }

    private function mapImportedAddressesContactsFromContactRelations(ContactRelationEntity ...$entities): array
    {
        $newAddresses = new Collection();
        $newContacts = new Collection();

        foreach ($entities as $contactRelation) {
            $owner = PipelinerClientEntityToUserProjector::from($contactRelation->contact->owner)();

            $address = $this->mapImportedAddressFromContactRelationEntity($contactRelation, $owner);
            $contact = $this->mapImportedContactFromContactRelationEntity($contactRelation, $owner);

            $newAddresses[] = $address->contact()->associate($contact->getKey());
            $newContacts[] = $contact;
        }

        return [$newAddresses, $newContacts];
    }

    private function mapImportedAddressFromContactRelationEntity(ContactRelationEntity $entity, User $owner): ImportedAddress
    {
        return tap(new ImportedAddress(), function (ImportedAddress $address) use ($entity, $owner): void {
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

    private function mapImportedContactFromContactRelationEntity(ContactRelationEntity $entity, User $owner): ImportedContact
    {
        return tap(new ImportedContact(), function (ImportedContact $contact) use ($entity, $owner): void {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
            $contact->pl_reference = $entity->contact->id;
            $contact->owner()->associate($owner);

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
        $fieldValue = ($this->pipelinerDataResolver)($entity->customFields['cfVatTypeId'] ?? null)?->optionName;

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
        $cfVendor = Collection::make($vendors)->map(function (Vendor $vendor): ?string {
            static $shortCodeDict = [
                'LEN' => 'Lenovo',
                'IBM' => 'IBM',
                'CIS' => 'Cisco',
                'DEL' => 'Dell',
                'HPE' => 'HPE',
                'FUJ' => 'Fujitsu',
                'VMW' => 'VM Ware',
            ];

            $optionName = (string)($shortCodeDict[$vendor->short_code] ?? $vendor->short_code);

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