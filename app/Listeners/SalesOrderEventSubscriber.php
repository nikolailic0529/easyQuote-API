<?php

namespace App\Listeners;

use App\Events\SalesOrder\SalesOrderDrafted;
use App\Jobs\IndexSearchableEntity;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;

class SalesOrderEventSubscriber
{
    protected BusDispatcher $busDispatcher;

    /**
     * SalesOrderEventSubscriber constructor.
     * @param BusDispatcher $busDispatcher
     */
    public function __construct(BusDispatcher $busDispatcher)
    {
        $this->busDispatcher = $busDispatcher;
    }

    /**
     * @param EventDispatcher $events
     */
    public function subscribe(EventDispatcher $events)
    {
        /** @noinspection PhpParamsInspection */
        $events->listen(SalesOrderDrafted::class, [$this, 'handleDraftedEvent']);
    }

    public function handleDraftedEvent(SalesOrderDrafted $event)
    {
        $salesOrder = $event->getSalesOrder();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($salesOrder)
        );
    }
}
