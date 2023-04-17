<?php

namespace App\Domain\Company\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Company\DataTransferObjects\AttachCompanyAddressData;
use App\Domain\Company\DataTransferObjects\AttachCompanyAddressNoBackrefData;
use App\Domain\Company\DataTransferObjects\AttachCompanyContactData;
use App\Domain\Company\DataTransferObjects\AttachCompanyContactNoBackrefData;
use App\Domain\Company\DataTransferObjects\BatchAttachCompanyAddressData;
use App\Domain\Company\DataTransferObjects\BatchAttachCompanyContactData;
use App\Domain\Company\DataTransferObjects\CreateCompanyData;
use App\Domain\Company\DataTransferObjects\PartialUpdateCompanyData;
use App\Domain\Company\DataTransferObjects\UpdateCompanyContactData;
use App\Domain\Company\DataTransferObjects\UpdateCompanyData;
use App\Domain\Company\Enum\CompanyStatusEnum;
use App\Domain\Company\Events\CompanyCreated;
use App\Domain\Company\Events\CompanyDeleted;
use App\Domain\Company\Events\CompanyUpdated;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyCategory;
use App\Domain\Contact\Models\Contact;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\User\Models\User;
use App\Foundation\DataTransferObject\MissingValue;
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

    public function __construct(
        protected LoggerInterface $logger,
        protected CompanyDataMapper $dataMapper,
        protected ValidatorInterface $validator,
        protected ConnectionInterface $connection,
        protected LockProvider $lockProvider,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    /**
     * Update a contact of the company.
     *
     * @throws \Throwable
     */
    public function updateCompanyContact(
        Company $company,
        Contact $contact,
        UpdateCompanyContactData $contactData
    ): Contact {
        return tap($contact, function (Contact $contact) use ($contactData, $company): void {
            $oldCompany = tap(new Company(), static function (Company $oldCompany) use ($company): void {
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

            $this->connection->transaction(static fn () => $contact->save());
            $this->eventDispatcher->dispatch(new CompanyUpdated(company: $company, oldCompany: $oldCompany));
        });
    }

    public function createCompany(CreateCompanyData $data)
    {
        return tap(new Company(), function (Company $company) use ($data): void {
            $company->name = $data->name;
            $company->vat = $data->vat;
            $company->vat_type = $data->vat_type;
            $company->type = $data->type;
            $company->source = $data->source;
            $company->short_code = $data->short_code;
            if (!$data->customer_type instanceof MissingValue) {
                $company->customer_type = $data->customer_type;
            }
            $company->email = $data->email;
            $company->phone = $data->phone;
            $company->website = $data->website;
            $company->status = CompanyStatusEnum::Active;
            $company->salesUnit()->associate($data->sales_unit_id);
            $company->defaultVendor()->associate($data->default_vendor_id);
            $company->defaultTemplate()->associate($data->default_template_id);
            $company->defaultCountry()->associate($data->default_country_id);

            if ($this->causer instanceof User) {
                $company->owner()->associate($this->causer);
            }

            $categories = CompanyCategory::query()->whereIn('name', $data->categories)->get();

            $addressesData = [];

            foreach ($data->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($data->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }

            $addressModel = new Address();
            $contactModel = new Contact();

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
            $this->connection->transaction(static function () use (
                $addresses,
                $data,
                $company,
                $addressesData,
                $contactsData,
                $categories,
            ): void {
                $addresses->each->save();

                $company->save();

                $company->vendors()->sync($data->vendors);
                $company->addresses()->sync($addressesData);
                $company->contacts()->sync($contactsData);
                $company->categories()->sync($categories);

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
        return tap($company, function (Company $company) use ($data): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $company->name = $data->name;
            $company->vat = $data->vat;
            $company->vat_type = $data->vat_type;
            $company->type = $data->type;
            $company->source = $data->source;
            $company->short_code = $data->short_code;
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

            $categories = CompanyCategory::query()->whereIn('name', $data->categories)->get();

            $addressesData = [];

            foreach ($data->addresses as $addressData) {
                $addressesData[$addressData->id] = ['is_default' => $addressData->is_default];
            }

            $contactsData = [];

            foreach ($data->contacts as $contactData) {
                $contactsData[$contactData->id] = ['is_default' => $contactData->is_default];
            }

            $addressModel = new Address();
            $contactModel = new Contact();

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
            $latestUpdatedAtOfContactRelations = value(static function () use (
                $contacts,
                $addresses,
                $addressModel,
                $contactModel
            ): ?Carbon {
                $max = collect([
                    $addresses->max($addressModel->getUpdatedAtColumn()),
                    $contacts->max($contactModel->getUpdatedAtColumn()),
                ])
                    ->max();

                return null !== $max ? Carbon::instance($max) : null;
            });

            $attributesWereChanged = false;
            $relationsWereChanged = false;

            // TODO: add company locking.
            $this->connection->transaction(static function () use (
                $addresses,
                $data,
                $company,
                $categories,
                $latestUpdatedAtOfContactRelations,
                &$relationsWereChanged,
                &$attributesWereChanged,
            ): void {
                $addresses->each->save();

                $relationChanges = [
                    'vendors' => $company->vendors()->sync($data->vendors),
//                    'addresses' => $company->addresses()->sync($addressesData),
//                    'contacts' => $company->contacts()->sync($contactsData),
                    'categories' => $company->categories()->sync($categories),
                ];

                $relationsWereChanged = collect($relationChanges)->lazy()->flatten()->isNotEmpty();

                if ($relationsWereChanged || $latestUpdatedAtOfContactRelations?->greaterThan($company->{$company->getUpdatedAtColumn()})) {
                    $company->updateTimestamps();
                }

                $company->save();

                $attributesWereChanged = $company->wasChanged();

                // TODO: refactor image processing.
                ThumbHelper::createLogoThumbnails($company, $data->logo);

                if ($data->delete_logo) {
                    $company->image()->flushQueryCache()->delete();
                }
            });

            $eventFlags = 0;
            if ($attributesWereChanged) {
                $eventFlags |= CompanyUpdated::ATTRIBUTES_CHANGED;
            }
            if ($relationsWereChanged) {
                $eventFlags |= CompanyUpdated::RELATIONS_CHANGED;
            }

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(
                    company: $company,
                    oldCompany: $oldCompany,
                    causer: $this->causer,
                    flags: $eventFlags,
                )
            );
        });
    }

    public function partiallyUpdateCompany(Company $company, PartialUpdateCompanyData $data): Company
    {
        return tap($company, function (Company $company) use ($data): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $company->forceFill((clone $data)->except('logo', 'delete_logo', 'addresses', 'contacts')->toArray());

            $addressesData = with($data->addresses, static function (?array $addresses): ?array {
                if (is_null($addresses)) {
                    return null;
                }

                return collect($addresses)
                    ->mapWithKeys(static fn (AttachCompanyAddressData $data
                    ) => [$data->id => ['is_default' => $data->is_default]])
                    ->all();
            });

            $contactsData = with($data->contacts, static function (?array $contacts): ?array {
                if (is_null($contacts)) {
                    return null;
                }

                return collect($contacts)
                    ->mapWithKeys(static fn (AttachCompanyContactData $data
                    ) => [$data->id => ['is_default' => $data->is_default]])
                    ->all();
            });

            $this->connection->transaction(static function () use ($data, $company, $addressesData, $contactsData): void {
                $company->save();

                if (!is_null($addressesData)) {
                    $company->addresses()->sync($addressesData);
                }

                if (!is_null($contactsData)) {
                    $company->contacts()->sync($contactsData);
                }

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

    public function batchAttachAddressToCompany(Company $company, BatchAttachCompanyAddressData $data): void
    {
        $oldCompany = $this->dataMapper->cloneCompany($company);

        $pivots = $data->addresses->toCollection()
            ->mapWithKeys(static function (AttachCompanyAddressNoBackrefData $data): array {
                return [$data->id => $data->except('id')->toArray()];
            })
            ->all();

        $this->connection->transaction(static function () use ($company, $pivots): void {
            $company->addresses()->syncWithoutDetaching($pivots);
        });

        $this->eventDispatcher->dispatch(
            new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
        );
    }

    public function batchAttachContactToCompany(Company $company, BatchAttachCompanyContactData $data): void
    {
        $oldCompany = $this->dataMapper->cloneCompany($company);

        $pivots = $data->contacts->toCollection()
            ->mapWithKeys(static function (AttachCompanyContactNoBackrefData $data): array {
                return [$data->id => $data->except('id')->toArray()];
            })
            ->all();

        $this->connection->transaction(static function () use ($company, $pivots): void {
            $company->contacts()->syncWithoutDetaching($pivots);
        });

        $this->eventDispatcher->dispatch(
            new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
        );
    }

    public function attachAddressToCompany(Company $company, Address $address): Company
    {
        return tap($company, function (Company $company) use ($address): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $this->connection->transaction(static function () use ($company, $address): void {
                $company->addresses()->syncWithoutDetaching($address);
            });

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
            );
        });
    }

    public function detachAddressFromCompany(Company $company, Address $address): Company
    {
        return tap($company, function (Company $company) use ($address): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $this->connection->transaction(static function () use ($company, $address): void {
                $company->addresses()->detach($address);
            });

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
            );
        });
    }

    public function attachContactToCompany(Company $company, Contact $contact): Company
    {
        return tap($company, function (Company $company) use ($contact): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $this->connection->transaction(static function () use ($company, $contact): void {
                $company->contacts()->syncWithoutDetaching($contact);
            });

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
            );
        });
    }

    public function detachContactFromCompany(Company $company, Contact $contact): Company
    {
        return tap($company, function (Company $company) use ($contact): void {
            $oldCompany = $this->dataMapper->cloneCompany($company);

            $this->connection->transaction(static function () use ($company, $contact): void {
                $company->contacts()->detach($contact);
            });

            $this->eventDispatcher->dispatch(
                new CompanyUpdated(company: $company, oldCompany: $oldCompany, causer: $this->causer)
            );
        });
    }

    public function markCompanyAsActive(Company $company): void
    {
        $company->activated_at = now();

        $this->connection->transaction(static function () use ($company): void {
            $company->save();
        });
    }

    public function markCompanyAsInactive(Company $company): void
    {
        $company->activated_at = null;

        $this->connection->transaction(static function () use ($company): void {
            $company->save();
        });
    }

    public function deleteCompany(Company $company): void
    {
        $this->connection->transaction(static function () use ($company): void {
            $company->delete();
        });

        $this->eventDispatcher->dispatch(
            new CompanyDeleted(company: $company, causer: $this->causer)
        );
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer): void {
            $this->causer = $causer;
        });
    }
}
