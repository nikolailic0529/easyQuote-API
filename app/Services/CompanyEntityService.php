<?php

namespace App\Services;

use App\Contracts\CauserAware;
use App\DTO\Company\AttachCompanyAddressData;
use App\DTO\Company\AttachCompanyContactData;
use App\DTO\Company\CreateCompanyData;
use App\DTO\Company\PartialUpdateCompanyData;
use App\DTO\Company\UpdateCompanyContactData;
use App\DTO\Company\UpdateCompanyData;
use App\DTO\MissingValue;
use App\Events\Company\CompanyCreated;
use App\Events\Company\CompanyDeleted;
use App\Events\Company\CompanyUpdated;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

    public function createCompany(CreateCompanyData $data)
    {
        return tap(new Company(), function (Company $company) use ($data) {
            $company->name = $data->name;
            $company->vat = $data->vat;
            $company->vat_type = $data->vat_type;
            $company->type = $data->type;
            $company->source = $data->source;
            $company->short_code = $data->short_code;
            $company->category = $data->category;
            if (!$data->customer_type instanceof MissingValue) {
                $company->customer_type = $data->customer_type;
            }
            $company->email = $data->email;
            $company->phone = $data->phone;
            $company->website = $data->website;
            $company->salesUnit()->associate($data->sales_unit_id);
            $company->defaultVendor()->associate($data->default_vendor_id);
            $company->defaultTemplate()->associate($data->default_template_id);
            $company->defaultCountry()->associate($data->default_country_id);

            if ($this->causer instanceof User) {
                $company->owner()->associate($this->causer);
            }

            $addressesData = [];

            foreach ($data->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($data->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }

            $addressModel = new Address;
            $contactModel = new Contact;

            $addresses = $addressModel->newQuery()->whereKey(array_keys($addressesData))->get();
            $contacts = $contactModel->newQuery()->whereKey(array_keys($contactsData))->get();

            // Associate addresses with the first contact, when not assigned yet.
            if ($contacts->isNotEmpty()) {
                $addresses->each(static function (Address $address) use ($contacts): void {
                    if (null === $address->contact()->getParentKey()) {
                        $address->contact()->associate($contacts->first());
                    }
                });
            }

            // TODO: add company locking.
            $this->connection->transaction(function () use ($addresses, $data, $company, $addressesData, $contactsData) {
                $addresses->each->save();

                $company->save();

                $company->vendors()->sync($data->vendors);
                $company->addresses()->sync($addressesData);
                $company->contacts()->sync($contactsData);

                // TODO: refactor image processing.
                ThumbHelper::createLogoThumbnails($company, $data->logo);
            });

            $this->eventDispatcher->dispatch(
                new CompanyCreated(company: $company, causer: $this->causer)
            );
        });
    }

    public function updateCompany(Company $company, UpdateCompanyData $data): Company
    {
        return tap($company, function (Company $company) use ($data) {
            $oldCompany = tap(new Company(), function (Company $oldCompany) use ($company) {
                $oldCompany->setRawAttributes($company->getRawOriginal());
                $oldCompany->load(['addresses', 'contacts', 'vendors']);
            });

            $company->name = $data->name;
            $company->vat = $data->vat;
            $company->vat_type = $data->vat_type;
            $company->type = $data->type;
            $company->source = $data->source;
            $company->short_code = $data->short_code;
            $company->category = $data->category;
            if (!$data->customer_type instanceof MissingValue) {
                $company->customer_type = $data->customer_type;
            }
            $company->email = $data->email;
            $company->phone = $data->phone;
            $company->website = $data->website;
            $company->salesUnit()->associate($data->sales_unit_id);
            $company->defaultVendor()->associate($data->default_vendor_id);
            $company->defaultTemplate()->associate($data->default_template_id);
            $company->defaultCountry()->associate($data->default_country_id);

            $addressesData = [];

            foreach ($data->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($data->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }

            $addressModel = new Address;
            $contactModel = new Contact;

            $addresses = $addressModel->newQuery()->whereKey(array_keys($addressesData))->get();
            $contacts = $contactModel->newQuery()->whereKey(array_keys($contactsData))->get();

            // Associate addresses with the first contact, when not assigned yet.
            if ($contacts->isNotEmpty()) {
                $addresses->each(static function (Address $address) use ($contacts): void {
                    if (null === $address->contact()->getParentKey()) {
                        $address->contact()->associate($contacts->first());
                    }
                });
            }

            /** @var ?Carbon $latestUpdatedAtOfContactRelations */
            $latestUpdatedAtOfContactRelations = value(static function () use ($contacts, $addresses, $addressModel, $contactModel): ?Carbon {
                $max = collect([
                    $addresses->max($addressModel->getUpdatedAtColumn()),
                    $contacts->max($contactModel->getUpdatedAtColumn()),
                ])
                    ->max();

                return null !== $max ? Carbon::instance($max) : null;
            });

            // TODO: add company locking.
            $this->connection->transaction(function () use ($addresses, $data, $company, $addressesData, $contactsData, $latestUpdatedAtOfContactRelations) {
                $addresses->each->save();

                if ($latestUpdatedAtOfContactRelations?->greaterThan($company->{$company->getUpdatedAtColumn()})) {
                    $company->updateTimestamps();
                }

                $company->save();

                $relationChanges = [];

                $relationChanges['vendors'] = $company->vendors()->sync($data->vendors);
                $relationChanges['addresses'] = $company->addresses()->sync($addressesData);
                $relationChanges['contacts'] = $company->contacts()->sync($contactsData);

                $relationsWereChanged = collect($relationChanges)->contains(static function (array $changes): bool {
                    foreach ($changes as $change) {
                        if (count($change) > 0) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($company->wasChanged() || $relationsWereChanged) {
                    $company->opportunities()->touch();
                    $company->opportunitiesWhereEndUser()->touch();
                }

                // TODO: refactor image processing.
                ThumbHelper::createLogoThumbnails($company, $data->logo);

                if ($data->delete_logo) {
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
        return tap($company, function (Company $company) use ($companyData) {
            $oldCompany = tap(new Company(), function (Company $oldCompany) use ($company) {
                $oldCompany->setRawAttributes($company->getRawOriginal());
                $oldCompany->load(['addresses', 'contacts', 'vendors']);
            });

            $company->name = $companyData->name;
            $company->email = $companyData->email;
            $company->phone = $companyData->phone;
            $company->website = $companyData->website;

            $addressesData = with($companyData->addresses, static function (?array $addresses): ?array {

                if (is_null($addresses)) {
                    return null;
                }

                return collect($addresses)
                    ->mapWithKeys(static fn(AttachCompanyAddressData $data) => [$data->id => ['is_default' => $data->is_default]])
                    ->all();

            });

            $contactsData = with($companyData->contacts, static function (?array $contacts): ?array {

                if (is_null($contacts)) {
                    return null;
                }

                return collect($contacts)
                    ->mapWithKeys(static fn(AttachCompanyContactData $data) => [$data->id => ['is_default' => $data->is_default]])
                    ->all();

            });

            $this->connection->transaction(function () use ($companyData, $company, $addressesData, $contactsData) {
                $company->save();

                if (!is_null($addressesData)) {
                    $company->addresses()->sync($addressesData);
                }

                if (!is_null($contactsData)) {
                    $company->contacts()->sync($contactsData);
                }

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
