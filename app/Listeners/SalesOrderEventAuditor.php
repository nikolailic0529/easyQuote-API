<?php

namespace App\Listeners;

use App\Events\SalesOrder\SalesOrderDrafted;
use App\Events\SalesOrder\SalesOrderExported;
use App\Jobs\IndexSearchableEntity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;

class SalesOrderEventAuditor
{
    public function __construct(protected BusDispatcher  $busDispatcher,
                                protected ActivityLogger $activityLogger)
    {
    }

    /**
     * @param EventDispatcher $events
     */
    public function subscribe(EventDispatcher $events)
    {
        $events->listen(SalesOrderDrafted::class, [$this, 'handleDraftedEvent']);
        $events->listen(SalesOrderExported::class, [$this, 'handleSalesOrderExportedEvent']);
    }

    public function handleDraftedEvent(SalesOrderDrafted $event)
    {
        $salesOrder = $event->getSalesOrder();

        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($salesOrder)
        );
    }

    public function handleSalesOrderExportedEvent(SalesOrderExported $event)
    {
        $salesOrder = $event->getSalesOrder();
        $quote = $salesOrder->worldwideQuote;

        $this->activityLogger
            ->on($quote)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'exported_file' => 'Sales Order as PDF',
                ],
            ])
            ->log('exported');

    }
}
