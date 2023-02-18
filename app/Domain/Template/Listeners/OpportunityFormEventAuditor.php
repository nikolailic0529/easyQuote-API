<?php

namespace App\Domain\Template\Listeners;

use App\Domain\Template\Events\OpportunityForm\OpportunityFormCreated;
use App\Domain\Template\Events\OpportunityForm\OpportunityFormDeleted;
use App\Domain\Template\Events\OpportunityForm\OpportunityFormUpdated;
use App\Foundation\Support\Elasticsearch\Jobs\DeleteSearchableEntity;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
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
