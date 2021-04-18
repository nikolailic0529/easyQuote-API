<?php

namespace App\Services;

use App\DTO\Company\CreateCompanyData;
use App\DTO\Company\UpdateCompanyContactData;
use App\DTO\Company\UpdateCompanyData;
use App\Events\Company\CompanyUpdated;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyEntityService
{
    protected LoggerInterface $logger;

    protected ValidatorInterface $validator;

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected EventDispatcher $eventDispatcher;

    /**
     * CompanyService constructor.
     * @param LoggerInterface $logger
     * @param ValidatorInterface $validator
     * @param ConnectionInterface $connection
     * @param LockProvider $lockProvider
     * @param \Illuminate\Contracts\Events\Dispatcher $eventDispatcher
     */
    public function __construct(LoggerInterface $logger,
                                ValidatorInterface $validator,
                                ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                EventDispatcher $eventDispatcher)
    {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->eventDispatcher = $eventDispatcher;
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
        return tap($contact, function (Contact $contact) use ($contactData) {
            $contact->first_name = $contactData->first_name;
            $contact->last_name = $contactData->last_name;
            $contact->phone = $contactData->phone;
            $contact->mobile = $contactData->mobile;
            $contact->email = $contactData->email;
            $contact->job_title = $contactData->job_title;
            $contact->is_verified = $contactData->is_verified;

            $this->connection->transaction(fn() => $contact->save());

            // TODO: dispatch "company updated" event.
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
        });
    }

    public function updateCompany(Company $company, UpdateCompanyData $companyData): Company
    {
        return tap($company, function (Company $company) use ($companyData) {
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
                new CompanyUpdated($company)
            );
        });
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
    }
}
