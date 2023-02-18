<?php

namespace App\Domain\Team\Listeners;

use App\Domain\Team\Events\TeamCreated;
use App\Domain\Team\Events\TeamDeleted;
use App\Domain\Team\Events\TeamUpdated;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;

class TeamEventSubscriber
{
    protected BusDispatcher $busDispatcher;

    public function __construct(BusDispatcher $busDispatcher)
    {
        $this->busDispatcher = $busDispatcher;
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(EventDispatcher $events)
    {
        $events->listen(TeamCreated::class, [$this, 'handleCreatedEvent']);
        $events->listen(TeamUpdated::class, [$this, 'handleUpdatedEvent']);
        $events->listen(TeamDeleted::class, [$this, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(TeamCreated $event)
    {
        $team = $event->getTeam();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($team)
        );
    }

    public function handleUpdatedEvent(TeamUpdated $event)
    {
        $team = $event->getTeam();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($team)
        );
    }

    public function handleDeletedEvent(TeamDeleted $event)
    {
    }
}
