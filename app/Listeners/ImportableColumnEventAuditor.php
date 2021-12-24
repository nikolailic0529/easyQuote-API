<?php

namespace App\Listeners;

use App\Events\ImportableColumn\ImportableColumnCreated;
use App\Events\ImportableColumn\ImportableColumnDeleted;
use App\Events\ImportableColumn\ImportableColumnUpdated;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Events\Dispatcher;

class ImportableColumnEventAuditor
{
    public function __construct(
        protected ActivityLogger $activityLogger,
        protected ChangesDetector $changesDetector,
    )
    {
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(ImportableColumnCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(ImportableColumnUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(ImportableColumnDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(ImportableColumnCreated $event)
    {
        $column = $event->getImportableColumn();

        $this->activityLogger
            ->performedOn($column)
            ->by($event->getCauser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'header' => $column->header,
                    'type' => $column->type,
                    'aliases' => $column->aliases->pluck('alias')->implode(', '),
                ],
            ])
            ->log('created');
    }

    public function handleUpdatedEvent(ImportableColumnUpdated $event)
    {
        $column = $event->getImportableColumn();
        $original = $event->getOriginalImportableColumn();

        $this->activityLogger
            ->performedOn($column)
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: [
                        'header' => $original->header,
                        'type' => $original->type,
                        'aliases' => $original->aliases->pluck('alias')->implode(', '),
                        'activated_at' => $original->activated_at,
                    ],
                    newAttributeValues: [
                        'header' => $column->header,
                        'type' => $column->type,
                        'aliases' => $column->aliases->pluck('alias')->implode(', '),
                        'activated_at' => $column->activated_at,
                    ]
                )
            )
            ->log('updated');
    }

    public function handleDeletedEvent(ImportableColumnDeleted $event)
    {
        $column = $event->getImportableColumn();

        $this->activityLogger
            ->performedOn($column)
            ->by($event->getCauser())
            ->log('deleted');
    }
}