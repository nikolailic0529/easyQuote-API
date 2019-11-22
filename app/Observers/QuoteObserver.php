<?php

namespace App\Observers;

use App\Models\Quote\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "created" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function created(Quote $quote)
    {
        return $quote->activate();
    }

    /**
     * Handle the Quote "deleting" event.
     *
     * @param  \App\Models\Quote\Quote  $quote
     * @return void
     */
    public function deleting(Quote $quote)
    {
        return $quote->countryMargin()->dissociate();
    }

    /**
     * Handle the Quote "saved" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function saved(Quote $quote)
    {
        $quote->shouldRecalculateMargin && $quote->calculateMarginPercentage();
    }
}
