<?php

namespace App\Listeners;

use App\Events\Pipeline\PipelineCreated;
use App\Events\Pipeline\PipelineDeleted;
use App\Events\Pipeline\PipelineUpdated;
use App\Jobs\DeleteSearchableEntity;
use App\Jobs\IndexSearchableEntity;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as Config;

class PipelineEventAuditor
{
    protected Config $config;

    protected BusDispatcher $busDispatcher;

    public function __construct(Config $config,
                                BusDispatcher $busDispatcher)
    {
        $this->config = $config;
        $this->busDispatcher = $busDispatcher;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events)
    {
        $events->listen(PipelineCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(PipelineUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(PipelineDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(PipelineCreated $event)
    {
        $pipeline = $event->getPipeline();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($pipeline)
        );
    }

    public function handleUpdatedEvent(PipelineUpdated $event)
    {
        $pipeline = $event->getPipeline();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($pipeline)
        );
    }

    public function handleDeletedEvent(PipelineDeleted $event)
    {
        $pipeline = $event->getPipeline();

        $this->busDispatcher->dispatch(
            new DeleteSearchableEntity($pipeline)
        );
    }
}
