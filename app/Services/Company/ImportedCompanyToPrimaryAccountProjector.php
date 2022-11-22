<?php

namespace App\Services\Company;

use App\Contracts\CauserAware;
use App\Contracts\Services\PermissionBroker;
use App\Enum\AddressType;
use App\Enum\CompanySource;
use App\Enum\CompanyType;
use App\Enum\VAT;
use App\Foundation\TemporaryDirectory;
use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyCategory;
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
use App\Services\Image\ThumbnailService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory as Client;
use Illuminate\Support\Str;

class ImportedCompanyToPrimaryAccountProjector implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected Client $client,
                                protected PermissionBroker $permissionBroker,
                                protected ImportedAddressToAddressProjector $addressProjector,
                                protected ImportedContactToContactProjector $contactProjector,
                                protected ThumbnailService $thumbnailService)
    {
    }

    private function saveCompanyPicture(Company $company, ImportedCompany $importedCompany): void
    {
        if (null === $importedCompany->picture_url) {
            return;
        }

        $originalFilename = null !== $company->image
            ? pathinfo($company->image->thumbnails['x3'], PATHINFO_BASENAME)
            : '';

        if ($originalFilename === $importedCompany->picture_filename) {
            return;
        }

        $tempDir = (new TemporaryDirectory())->create();
        $tempPath = $tempDir->path($importedCompany->picture_filename);

        $r = $this->client
            ->sink($tempPath)
            ->get($importedCompany->picture_url)->throw();

        $company->image()->delete();
        $company->image()->flushQueryCache();

        $this->thumbnailService->createThumbnailsFor(new \SplFileInfo($tempPath), $company);
    }

    public function __invoke(ImportedCompany $importedCompany): Company
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
            $company->customer_type = $importedCompany->customer_type;
            $company->source = CompanySource::PL;
            $company->flags |= Company::FROZEN_SOURCE;
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

            if (null !== $importedCompany->pl_reference) {
                $company->pl_reference = $importedCompany->pl_reference;
            }

            if (null !== $importedCompany->salesUnit) {
                $company->salesUnit()->associate($importedCompany->salesUnit);
            }

            $company->vat = coalesce_blank($company->vat, $importedCompany->vat);
            $company->email = coalesce_blank($company->email, $importedCompany->email);
            $company->phone = coalesce_blank($company->phone, $importedCompany->phone);
            $company->website = coalesce_blank($company->website, $importedCompany->website);

            $this->saveCompanyPicture($company, $importedCompany);
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

        $categories = CompanyCategory::query()
            ->whereIn('name', $importedCompany->company_categories)
            ->get();

        $this->connection->transaction(static function () use ($categories, $vendors, $company): void {
            $company->withoutTimestamps(static function (Company $company) use ($categories, $vendors): void {
                $company->save();
                $company->vendors()->syncWithoutDetaching($vendors);
                $company->categories()->syncWithoutDetaching($categories);
            });
        });

        $addressHashResolver = new AddressHashResolver();
        $contactHashResolver = new ContactHashResolver();

        $existingAddressHashes = $company->addresses->keyBy($addressHashResolver);
        $existingContactHashes = $company->contacts->keyBy($contactHashResolver);

        /** @var $importedAddressesHaveRelThroughPlRef Collection */
        /** @var $importedAddressesDontHaveRelThroughPlRef Collection */
        [$importedAddressesHaveRelThroughPlRef, $importedAddressesDontHaveRelThroughPlRef] =
            $importedCompany->addresses
                ->partition(static function (ImportedAddress $importedAddress) use ($company): bool {
                    if (null === $importedAddress->pl_reference) {
                        return false;
                    }

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
            $importedCompany->contacts
                ->partition(static function (ImportedContact $importedContact) use ($company): bool {
                    if (null === $importedContact->pl_reference) {
                        return false;
                    }

                    return $company->contacts->containsStrict('pl_reference', $importedContact->pl_reference);
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

        $importedAddressHashes = $importedAddressesDontHaveRelThroughPlRef->keyBy($addressHashResolver);
        $importedContactHashes = $importedContactsDontHaveRelThroughPlRef->keyBy($contactHashResolver);

        $newImportedAddresses = $importedAddressHashes->diffKeys($existingAddressHashes);
        $newImportedContacts = $importedContactHashes->diffKeys($existingContactHashes);

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

        $newAddresses = $newAddressMap->values();
        $newContacts = $newContactMap->values();

        $defaultInvoiceAddress = $company->addresses
            ->sortByDesc('pivot.is_default')
            ->firstWhere('address_type', '===', AddressType::INVOICE);

        $defaultInvoiceAddress = tap($defaultInvoiceAddress ?? new Address(), static function (Address $address) use (
            $importedCompany
        ): void {
            $address->address_type = AddressType::INVOICE;
            $address->address_1 = $importedCompany->address_1;
            $address->address_2 = $importedCompany->address_2;
            $address->city = $importedCompany->city;
            $address->post_code = $importedCompany->post_code;
            $address->state = $importedCompany->state;
            $address->state_code = $importedCompany->state_code;
            $address->country()->associate(
                Country::query()->where('name', $importedCompany->country_name)->first()
            );
        });

        $hwSwCountryMap = [
            AddressType::HARDWARE => isset($importedCompany->hw_country_code)
                ? Country::query()->where('iso_3166_2', $importedCompany->hw_country_code)->first()
                : null,
            AddressType::SOFTWARE => isset($importedCompany->sw_country_code)
                ? Country::query()->where('iso_3166_2', $importedCompany->sw_country_code)->first()
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

        $this->connection->transaction(static function () use ($newAddresses, $defaultInvoiceAddress, $company): void {
            $company->addresses->each->push();
            $defaultInvoiceAddress->save();

            $company->addresses()->syncWithoutDetaching([$defaultInvoiceAddress->getKey() => ['is_default' => true]]);

            if ($newAddresses->isNotEmpty()) {
                $newAddresses->each->push();
                $company->addresses()->syncWithoutDetaching($newAddresses->values());
            }
        });

        $this->connection->transaction(static function () use ($newContacts, $company): void {
            $company->contacts->each(static function (Contact $contact): void {
                $contact->user?->save();
                $contact->address?->save();
                $contact->save();
            });

            if ($newContacts->isNotEmpty()) {
                $newContacts->each(static function (Contact $contact): void {
                    $contact->user?->save();
                    $contact->address?->save();
                    $contact->save();
                });

                $newContacts->each->push();
                $company->contacts()->syncWithoutDetaching($newContacts->values());
            }
        });

        return $company;
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}