<?php

namespace App\Listeners;

use App\Events\CompanyNote\CompanyNoteCreated;
use App\Events\CompanyNote\CompanyNoteDeleted;
use App\Events\CompanyNote\CompanyNoteUpdated;
use App\Services\Activity\ActivityLogger;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;

class CompanyNoteAuditor
{
    public function __construct(protected ActivityLogger $activityLogger)
    {
    }

    public function subscribe(EventsDispatcher $events)
    {
        $events->listen(CompanyNoteCreated::class, [self::class, 'handleNoteCreated']);
        $events->listen(CompanyNoteUpdated::class, [self::class, 'handleNoteUpdated']);
        $events->listen(CompanyNoteDeleted::class, [self::class, 'handleNoteDeleted']);
    }

    public function handleNoteCreated($event)
    {
        $entity = $event->getCompany();

        $this->activityLogger
            ->performedOn($entity)
            ->by($event->getCauser())
            ->withProperties([
                'old' => [],
                'attributes' => ['note' => $event->getNoteText()],
            ])
            ->log('created');
    }

    public function handleNoteUpdated($event)
    {
        $entity = $event->getEntity();
        $note = $event->getNote();

        $this->activityLogger
            ->performedOn($entity)
            ->by($event->getCauser())
            ->withProperties([
                'old' => [
                    'note' => $entity->companyNotes()->oldest()->find($note->id)->whereKey($note->getKey())->pluck('text')->first(),
                ],
                'attributes' => [
                    'note' => $event->getNoteText(),
                ],
            ])
            ->log('updated');

    }

    public function handleNoteDeleted($event)
    {
        $entity = $event->getCompany();
        $note = $event->getNote();

        $this->activityLogger
            ->performedOn($entity)
            ->by($event->getCauser())
            ->withProperties([
                'old' => [
                    'note' => $event->getNoteText(),
                ],
                'attributes' => [
                    'note' => '',
                ],
            ])
            ->log('deleted');
    }
}
