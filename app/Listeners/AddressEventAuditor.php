<?php

namespace App\Listeners;

use App\Events\Address\AddressCreated;
use App\Events\Address\AddressDeleted;
use App\Events\Address\AddressUpdated;
use App\Models\Address;
use App\Models\Company;
use App\Models\Opportunity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use App\Services\Opportunity\ValidateOpportunityService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class AddressEventAuditor implements ShouldQueue
{
    protected static array $logAttributes = [
        'address_type',
        'address_1',
        'address_2',
        'city',
        'state',
        'post_code',
        'country.iso_3166_2',
        'contact_phone',
        'contact_name',
        'contact_email',
    ];

    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly ValidateOpportunityService $validateOpportunityService,
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            AddressCreated::class => [
                [static::class, 'handleCreatedEvent'],
                [static::class, 'touchRelatedCompaniesOnAddressCreated'],
                [static::class, 'revalidateRelatedOpportunitiesOnAddressCreated'],
            ],
            AddressUpdated::class => [
                [static::class, 'handleUpdatedEvent'],
                [static::class, 'touchRelatedCompaniesOnAddressUpdated'],
                [static::class, 'revalidateRelatedOpportunitiesOnAddressUpdated'],
            ],
            AddressDeleted::class => [
                [static::class, 'handleDeletedEvent'],
                [static::class, 'touchRelatedCompaniesOnAddressDeleted'],
                [static::class, 'revalidateRelatedOpportunitiesOnAddressDeleted'],
            ],
        ];
    }

    public function revalidateRelatedOpportunitiesOnAddressCreated(AddressCreated $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->address);
    }

    private function revalidateRelatedOpportunitiesOf(Address $address): void
    {
        $address->companies()->lazyById(10)
            ->each(function (Company $company): void {
                $opportunities = Opportunity::query()
                    ->where(static function (Builder $builder) use ($company): void {
                        $builder->whereBelongsTo($company, 'primaryAccount')
                            ->orWhereBelongsTo($company, 'endUser');
                    })
                    ->lazyById(100);

                foreach ($opportunities as $opp) {
                    $this->validateOpportunityService->performValidation($opp);
                }
            });
    }

    public function revalidateRelatedOpportunitiesOnAddressUpdated(AddressUpdated $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->newAddress);
    }

    public function revalidateRelatedOpportunitiesOnAddressDeleted(AddressDeleted $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->address);
    }

    public function touchRelatedCompaniesOnAddressCreated(AddressCreated $event): void
    {
        $event->address->companies()->touch();
    }

    public function touchRelatedCompaniesOnAddressUpdated(AddressUpdated $event): void
    {
        $event->newAddress->companies()->touch();
    }

    public function touchRelatedCompaniesOnAddressDeleted(AddressDeleted $event): void
    {
        $event->address->companies()->touch();
    }

    public function handleCreatedEvent(AddressCreated $event): void
    {
        $this->activityLogger
            ->on($event->address)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged($event->address, self::$logAttributes)
            )
            ->log('created');
    }

    public function handleUpdatedEvent(AddressUpdated $event): void
    {
        $this->activityLogger
            ->on($event->address)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    model: $event->newAddress,
                    logAttributes: self::$logAttributes,
                    oldAttributeValues: $this->changesDetector->getModelChanges($event->address,
                        self::$logAttributes),
                    diff: true,
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function handleDeletedEvent(AddressDeleted $event): void
    {
        $this->activityLogger
            ->on($event->address)
            ->by($event->causer)
            ->log('deleted');
    }
}
