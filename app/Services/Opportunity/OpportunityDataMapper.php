<?php

namespace App\Services\Opportunity;

use App\Enum\AccountCategory;
use App\Enum\AddressType;
use App\Enum\ContactType;
use App\Models\Data\Country;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Services\Opportunity\Models\PipelinerOppMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class OpportunityDataMapper
{
    const DEFAULT_OPP_TYPE = CT_PACK;

    protected array $countryNameOfSupplierCache = [];

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
            ->filter(static function (array $supplier): bool {
                foreach (['supplier_name', 'country_name', 'contact_name', 'contact_email'] as $key) {
                    if (filled($supplier[$key])) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    public function mapContractType(array $row): string
    {
        $typeString = (string)OpportunityDataMapper::coalesceMap($row, PipelinerOppMap::CONTRACT_TYPE);

        return match (trim(strtolower($typeString))) {
            'pack' => CT_PACK,
            'contract' => CT_CONTRACT,
            default => self::DEFAULT_OPP_TYPE,
        };
    }

    private function normalizeCountryNameOfSupplier(?string $countryName): ?string
    {
        if (is_null($countryName) || trim($countryName) === '') {
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

    private function mapAddressesOfAccount(array $account, array $contacts): Collection
    {
        $newAddressDataOfCompany = new Collection();

        foreach ($contacts as $contactData) {
            $newAddressDataOfCompany[] = tap(new ImportedAddress(), static function (ImportedAddress $address) use ($account, $contactData): void {
                $address->address_type = AddressType::INVOICE;

                [$addressOne, $addressTwo] = self::splitStreetAddress($contactData['street_address'] ?? null);

                $address->address_1 = $addressOne;
                $address->address_2 = $addressTwo;

                $address->city = $contactData['city'] ?? null;
                $address->post_code = $contactData['zip_code'] ?? null;
                $address->state = coalesce_blank($contactData['state_province'] ?? null, $account['state_province'] ?? null);

                if (isset($contactData['country'])) {
                    $address->country()->associate(
                        Country::query()->where('name', $contactData['country'])->first()
                    );
                } else {
                    $address->country()->disassociate();
                }

                $address->{$address->getCreatedAtColumn()} = $address->freshTimestampString();
                $address->{$address->getUpdatedAtColumn()} = $address->freshTimestampString();
            });
        }

        return $newAddressDataOfCompany;
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
            $newContactDataOfCompany[] = tap(new ImportedContact(), static function (ImportedContact $contact) use ($contactData): void {
                $contact->contact_type = ContactType::HARDWARE;
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

        return $newContactDataOfCompany;
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
}