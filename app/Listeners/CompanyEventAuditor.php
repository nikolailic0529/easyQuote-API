<?php

namespace App\Listeners;

use App\Events\Company\CompanyCreated;
use App\Events\Company\CompanyDeleted;
use App\Events\Company\CompanyUpdated;
use App\Models\Address;
use App\Models\Contact;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Events\Dispatcher;

class CompanyEventAuditor
{
    protected static array $logAttributes = [
        'name',
        'category',
        'vat',
        'type',
        'email',
        'category',
        'website',
        'phone',
        'defaultVendor.name',
        'defaultCountry.name',
        'defaultTemplate.name',
    ];

    public function __construct(protected ActivityLogger  $activityLogger,
                                protected ChangesDetector $changesDetector)
    {
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(CompanyCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(CompanyUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(CompanyDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(CompanyCreated $event): void
    {
        $company = $event->getCompany();

        $changes = $this->changesDetector->getAttributeValuesToBeLogged($company, self::$logAttributes);

        $changes[ChangesDetector::NEW_ATTRS_KEY] = array_merge($changes[ChangesDetector::NEW_ATTRS_KEY], [
            'addresses' => $company->addresses->map(fn (Address $address) => "\{$address->address_representation\}")->implode(', '),
            'contacts' => $company->contacts->map(fn (Contact $contact) => "\{$contact->contact_representation\}")->implode(', '),
            'vendors' => $company->vendors->pluck('short_code')->implode(', '),
        ]);

        $this->activityLogger
            ->on($company)
            ->by($event->getCauser())
            ->withProperties(
                $changes,
            )
            ->log('created');
    }

    public function handleUpdatedEvent(CompanyUpdated $event): void
    {
        $company = $event->getCompany();
        $oldCompany = $event->getOldCompany();

        $newAttributes = $this->changesDetector->getModelChanges($company, self::$logAttributes);
        $newAttributes = array_merge($newAttributes, [
            'addresses' => $company->addresses->map(fn (Address $address) => "\{$address->address_representation\}")->implode(', '),
            'contacts' => $company->contacts->map(fn (Contact $contact) => "\{$contact->contact_representation\}")->implode(', '),
            'vendors' => $company->vendors->pluck('short_code')->implode(', '),
        ]);

        $oldAttributes = $this->changesDetector->getModelChanges($oldCompany, self::$logAttributes);
        $oldAttributes = array_merge($oldAttributes, [
            'addresses' => $oldCompany->addresses->map(fn (Address $address) => "\{$address->address_representation\}")->implode(', '),
            'contacts' => $oldCompany->contacts->map(fn (Contact $contact) => "\{$contact->contact_representation\}")->implode(', '),
            'vendors' => $oldCompany->vendors->pluck('short_code')->implode(', '),
        ]);

        $this->activityLogger
            ->on($company)
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $oldAttributes,
                    newAttributeValues: $newAttributes,
                )
            )
            ->log('updated');
    }

    public function handleDeletedEvent(CompanyDeleted $event): void
    {
        $this->activityLogger
            ->on($event->getCompany())
            ->by($event->getCauser())
            ->log('deleted');
    }
}