<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;

class SyncWorldwideContractQuoteWithOpportunityData
{
    protected ProcessesWorldwideQuoteState $quoteProcessor;

    /**
     * Create the event listener.
     */
    public function __construct(ProcessesWorldwideQuoteState $quoteProcessor)
    {
        $this->quoteProcessor = $quoteProcessor;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(OpportunityUpdated $event)
    {
        $opportunity = $event->getOpportunity();

        foreach ($opportunity->worldwideQuotes as $quote) {
            $this->quoteProcessor->syncQuoteWithOpportunityData($quote);
        }
    }
}
