<?php

namespace App\Services;

use App\Contracts\CauserAware;
use App\DTO\Company\AttachCompanyAddressData;
use App\DTO\Company\AttachCompanyContactData;
use App\DTO\Company\CreateCompanyData;
use App\DTO\Company\PartialUpdateCompanyData;
use App\DTO\Company\UpdateCompanyContactData;
use App\DTO\Company\UpdateCompanyData;
use App\Events\Company\CompanyCreated;
use App\Events\Company\CompanyDeleted;
use App\Events\Company\CompanyUpdated;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected LoggerInterface     $logger,
                                protected ValidatorInterface  $validator,
                                protected ConnectionInterface $connection,
                                protected LockProvider        $lockProvider,
                                protected EventDispatcher     $eventDispatcher)
    {
    }

    /**
     * Update a contact of the company.
     *
     * @param Company $company
     * @param Contact $contact
     * @param UpdateCompanyContactData $contactData
     * @return Contact
     * @throws \Throwable
     */
    public function updateCompanyContact(Company $company, Contact $contact, UpdateCompanyContactData $contactData): Contact
    {
        return tap($contact, function (Contact $contact) use ($contactData, $company) {
            $oldCompany = tap(new Company(), function (Company $oldCompany) use ($company) {
                $oldCompany->setRawAttributes($company->getRawOriginal());
                $oldCompany->load(['addresses', 'contacts', 'vendors']);
            });

            $contact->first_name = $contactData->first_name;
            $contact->last_name = $contactData->last_name;
            $contact->phone = $contactData->phone;
            $contact->mobile = $contactData->mobile;
            $contact->email = $contactData->email;
            $contact->job_title = $contactData->job_title;
            $contact->is_verified = $contactData->is_verified;

            $this->connection->transaction(fn() => $contact->save());
            $this->eventDispatcher->dispatch(new CompanyUpdated(company: $company, oldCompany: $oldCompany));
        });
    }

    public function createCompany(CreateCompanyData $companyData)
    {
        return tap(new Company(), function (Company $company) use ($companyData) {
            $company->name = $companyData->name;
            $company->vat = $companyData->vat;
            $company->vat_type = $companyData->vat_type;
            $company->type = $companyData->type;
            $company->source = $companyData->source;
            $company->short_code = $companyData->short_code;
            $company->category = $companyData->category;
            $company->email = $companyData->email;
            $company->phone = $companyData->phone;
            $company->website = $companyData->website;
            $company->defaultVendor()->associate($companyData->default_vendor_id);
            $company->defaultTemplate()->associate($companyData->default_template_id);
            $company->defaultCountry()->associate($companyData->default_country_id);

            $addressesData = [];

            foreach ($companyData->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($companyData->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }

            // TODO: add company locking.
            $this->connection->transaction(function () use ($companyData, $company, $addressesData, $contactsData) {
                $company->save();

                $company->vendors()->sync($companyData->vendors);
                $company->addresses()->sync($addressesData);
                $company->contacts()->sync($contactsData);

                // TODO: refactor image processing.
                ThumbHelper::createLogoThumbnails($company, $companyData->logo);
            });

            $this->eventDispatcher->dispatch(
                new CompanyCreated(company: $company, causer: $this->causer)
            );
        });
    }

    public function updateCompany(Company $company, UpdateCompanyData $companyData): Company
    {
        return tap($company, function (Company $company) use ($companyData) {
            $oldCompany = tap(new Company(), function (Company $oldCompany) use ($company) {
                $oldCompany->setRawAttributes($company->getRawOriginal());
                $oldCompany->load(['addresses', 'contacts', 'vendors']);
            });

            $company->name = $companyData->name;
            $company->vat = $companyData->vat;
            $company->vat_type = $companyData->vat_type;
            $company->type = $companyData->type;
            $company->source = $companyData->source;
            $company->short_code = $companyData->short_code;
            $company->category = $companyData->category;
            $company->email = $companyData->email;
            $company->phone = $companyData->phone;
            $company->website = $companyData->website;
            $company->defaultVendor()->associate($companyData->default_vendor_id);
            $company->defaultTemplate()->associate($companyData->default_template_id);
            $company->defaultCountry()->associate($companyData->default_country_id);

            $addressesData = [];

            foreach ($companyData->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($companyData->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }


            // TODO: add company locking.
            $this->connection->transaction(function () use ($companyData, $company, $addressesData, $contactsData) {
                $company->save();

                $company->vendors()->sync($companyData->vendors);
                $company->addresses()->sync($addressesData);
                $company->contacts()->sync($contactsData);

                // TODO: refactor image processing.
                ThumbHelper::createLogoThumbnails($company, $companyData->logo);

                if ($companyData->delete_logo) {
                    $company->image()->flushQueryCache()->delete();
                }
            });

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
            );
        });
    }

    public function partiallyUpdateCompany(Company $company, PartialUpdateCompanyData $companyData): Company
    {
        return $this->updateCompany($company, new UpdateCompanyData([
            'name' => $companyData->name,
            'vat' => $companyData->vat,
            'vat_type' => $companyData->vat_type,
            'logo' => $companyData->logo,
            'delete_logo' => $companyData->delete_logo,
            'email' => $companyData->email,
            'phone' => $companyData->phone,
            'website' => $companyData->website,
            'addresses' => $companyData->addresses ?? $company->addresses->map(function (Address $address) {
                    return new AttachCompanyAddressData([
                        'id' => $address->getKey(),
                        'is_default' => (bool)$address->pivot?->is_default,
                    ]);
                })
                    ->all(),
            'contacts' => $companyData->contacts ?? $company->contacts->map(function (Contact $contact) {
                    return new AttachCompanyContactData([
                        'id' => $contact->getKey(),
                        'is_default' => (bool)$contact->pivot?->is_default,
                    ]);
                })
                    ->all(),

            // Persisting of the existing attributes
            'type' => $company->type,
            'source' => $company->source,
            'short_code' => $company->short_code,
            'category' => $company->category,
            'vendors' => $company->vendors()->get()->modelKeys(),
            'default_vendor_id' => $company->default_vendor_id,
            'default_template_id' => $company->default_template_id,
            'default_country_id' => $company->default_country_id,
        ]));
    }

    public function markCompanyAsActive(Company $company): void
    {
        $company->activated_at = now();

        $this->connection->transaction(function () use ($company) {
            $company->save();
        });
    }

    public function markCompanyAsInactive(Company $company): void
    {
        $company->activated_at = null;

        $this->connection->transaction(function () use ($company) {
            $company->save();
        });
    }

    public function deleteCompany(Company $company): void
    {
        $this->connection->transaction(function () use ($company) {
            $company->delete();
        });

        $this->eventDispatcher->dispatch(
            new CompanyDeleted(company: $company, causer: $this->causer)
        );
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
