<?php

namespace App\Listeners;

use App\Events\OpportunityForm\OpportunityFormCreated;
use App\Events\OpportunityForm\OpportunityFormDeleted;
use App\Events\OpportunityForm\OpportunityFormUpdated;
use App\Events\Pipeline\PipelineCreated;
use App\Events\Pipeline\PipelineDeleted;
use App\Events\Pipeline\PipelineUpdated;
use App\Jobs\DeleteSearchableEntity;
use App\Jobs\IndexSearchableEntity;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as Config;

class OpportunityFormEventAuditor
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
        $events->listen(OpportunityFormCreated::class, [self::class, 'handleCreatedEvent']);
        $events->listen(OpportunityFormUpdated::class, [self::class, 'handleUpdatedEvent']);
        $events->listen(OpportunityFormDeleted::class, [self::class, 'handleDeletedEvent']);
    }

    public function handleCreatedEvent(OpportunityFormCreated $event)
    {
        $opportunityForm = $event->getOpportunityForm();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($opportunityForm)
        );
    }

    public function handleUpdatedEvent(OpportunityFormUpdated $event)
    {
        $opportunityForm = $event->getOpportunityForm();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($opportunityForm)
        );
    }

    public function handleDeletedEvent(OpportunityFormDeleted $event)
    {
        $opportunityForm = $event->getOpportunityForm();

        $this->busDispatcher->dispatch(
            new DeleteSearchableEntity($opportunityForm)
        );
    }
}
