<?php

namespace App\Domain\Note\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Note\Events\NoteCreated;
use App\Domain\Note\Events\NoteDeleted;
use App\Domain\Note\Events\NoteUpdated;
use Illuminate\Events\Dispatcher;

class NoteEventAuditor
{
    public function __construct(protected ActivityLogger $activityLogger)
    {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            NoteCreated::class => [
                [static::class, 'auditCreatedEvent'],
            ],
            NoteUpdated::class => [
                [static::class, 'auditUpdatedEvent'],
            ],
            NoteDeleted::class => [
                [static::class, 'auditDeletedEvent'],
            ],
        ];
    }

    public function auditCreatedEvent(NoteCreated $event): void
    {
        $this->activityLogger
            ->on($event->note)
            ->by($event->causer)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'note' => $event->note->note,
                ],
            ])
            ->log('created');
    }

    public function auditUpdatedEvent(NoteUpdated $event): void
    {
        $this->activityLogger
            ->on($event->note)
            ->by($event->causer)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'note' => $event->oldNote->note,
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'note' => $event->note->note,
                ],
            ])
            ->log('updated');
    }

    public function auditDeletedEvent(NoteDeleted $event): void
    {
        $this->activityLogger
            ->on($event->note)
            ->by($event->causer)
            ->log('deleted');
    }
}
