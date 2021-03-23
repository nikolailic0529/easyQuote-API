<?php

namespace App\Listeners;

use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Events\Opportunity\OpportunityUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncOpportunitySuppliersWithWorldwideContractQuote
{
    /**
     * @var ProcessesWorldwideQuoteState
     */
    protected ProcessesWorldwideQuoteState $quoteProcessor;

    /**
     * Create the event listener.
     *
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     */
    public function __construct(ProcessesWorldwideQuoteState $quoteProcessor)
    {
        $this->quoteProcessor = $quoteProcessor;
    }

    /**
     * Handle the event.
     *
     * @param OpportunityUpdated $event
     * @return void
     */
    public function handle(OpportunityUpdated $event)
    {
        $opportunity = $event->getOpportunity();

        if (!is_null($opportunity->worldwideQuote)) {
            $this->quoteProcessor->syncContractQuoteWithOpportunityData($opportunity->worldwideQuote);
        }
    }
}
