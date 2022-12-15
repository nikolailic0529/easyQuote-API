<?php

namespace App\Listeners;

use App\Events\Contact\ContactCreated;
use App\Events\Contact\ContactDeleted;
use App\Events\Contact\ContactUpdated;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Events\Dispatcher;

class ContactEventAuditor
{
    protected static array $logAttributes = [
        'contact_type',
        'job_title',
        'first_name',
        'last_name',
        'mobile',
        'phone',
        'email',
        'is_verified',
    ];

    public function __construct(protected ActivityLogger  $activityLogger,
                                protected ChangesDetector $changesDetector)
    {
    }


    public function subscribe(Dispatcher $events)
    {
        $events->listen(ContactCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(ContactUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(ContactDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(ContactCreated $event)
    {
        $this->activityLogger
            ->on($event->getContact())
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged($event->getContact(), self::$logAttributes)
            )
            ->log('created');
    }

    public function handleUpdatedEvent(ContactUpdated $event)
    {
        $this->activityLogger
            ->on($event->getContact())
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    model: $event->getNewContact(), logAttributes: self::$logAttributes,
                    oldAttributeValues: $this->changesDetector->getModelChanges($event->getContact(), self::$logAttributes),
                    diff: true
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function handleDeletedEvent(ContactDeleted $event)
    {
        $this->activityLogger
            ->on($event->getContact())
            ->by($event->getCauser())
            ->log('deleted');

    }
}
