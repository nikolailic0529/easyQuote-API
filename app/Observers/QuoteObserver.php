<?php

namespace App\Observers;

use App\Models\Quote\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "deleting" event.
     *
     * @param  \App\Models\Quote\Quote  $quote
     * @return void
     */
    public function deleting(Quote $quote)
    {
        $quote->countryMargin()->dissociate();
    }
}
