<?php

namespace App\Listeners;

use App\Events\Team\TeamCreated;
use App\Events\Team\TeamDeleted;
use App\Events\Team\TeamUpdated;
use App\Jobs\IndexSearchableEntity;
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
     *
     * @param EventDispatcher $events
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
        //
    }
}
