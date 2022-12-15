<?php

namespace App\Listeners;

use App\Events\Address\AddressCreated;
use App\Events\Address\AddressDeleted;
use App\Events\Address\AddressUpdated;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Events\Dispatcher;

class AddressEventAuditor
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
        protected ActivityLogger $activityLogger,
        protected ChangesDetector $changesDetector
    ) {
    }


    public function subscribe(Dispatcher $events): void
    {
        $events->listen(AddressCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(AddressUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(AddressDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(AddressCreated $event): void
    {
        $this->activityLogger
            ->on($event->getAddress())
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged($event->getAddress(), self::$logAttributes)
            )
            ->log('created');
    }

    public function handleUpdatedEvent(AddressUpdated $event): void
    {
        $this->activityLogger
            ->on($event->getAddress())
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    model: $event->getNewAddress(),
                    logAttributes: self::$logAttributes,
                    oldAttributeValues: $this->changesDetector->getModelChanges($event->getAddress(),
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
            ->on($event->getAddress())
            ->by($event->getCauser())
            ->log('deleted');

    }

}
