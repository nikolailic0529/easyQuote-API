<?php

namespace App\Listeners;

use App\Events\RescueQuote\NoteCreated;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Events\Dispatcher;

class NoteEventAuditor
{
    public function __construct(protected ActivityLogger $activityLogger)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(NoteCreated::class, [$this, 'handleNoteCreatedEvent']);
    }

    public function handleNoteCreatedEvent(NoteCreated $event): void
    {
        $this->activityLogger
            ->on($event->model)
            ->by($event->note->owner)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'new_notes' => $event->note->note,
                ],
            ])
            ->log('updated');
    }
}