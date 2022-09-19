<?php

namespace App\Listeners;

use App\Events\Company\CompanyCreated;
use App\Events\Company\CompanyDeleted;
use App\Events\Company\CompanyUpdated;
use App\Jobs\Opportunity\ValidateOpportunitiesOfCompany;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use App\Services\Opportunity\ValidateOpportunityService;
use Elasticsearch\Endpoints\Ml\Validate;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\LazyCollection;

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

    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly BusDispatcher $busDispatcher,
    ) {
    }

    public function subscribe(Dispatcher $events): void
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
            'addresses' => $company->addresses->map(fn(Address $address) => "\{$address->address_representation\}")
                ->implode(', '),
            'contacts' => $company->contacts->map(fn(Contact $contact) => "\{$contact->contact_representation\}")
                ->implode(', '),
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
            'addresses' => $company->addresses->map(fn(Address $address) => "\{$address->address_representation\}")
                ->implode(', '),
            'contacts' => $company->contacts->map(fn(Contact $contact) => "\{$contact->contact_representation\}")
                ->implode(', '),
            'vendors' => $company->vendors->pluck('short_code')->implode(', '),
        ]);

        $oldAttributes = $this->changesDetector->getModelChanges($oldCompany, self::$logAttributes);
        $oldAttributes = array_merge($oldAttributes, [
            'addresses' => $oldCompany->addresses->map(fn(Address $address) => "\{$address->address_representation\}")
                ->implode(', '),
            'contacts' => $oldCompany->contacts->map(fn(Contact $contact) => "\{$contact->contact_representation\}")
                ->implode(', '),
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

        $this->busDispatcher->dispatch(new ValidateOpportunitiesOfCompany($company));
    }

    public function handleDeletedEvent(CompanyDeleted $event): void
    {
        $this->activityLogger
            ->on($event->getCompany())
            ->by($event->getCauser())
            ->log('deleted');
    }
}