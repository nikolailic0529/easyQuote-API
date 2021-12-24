<?php

namespace App\Observers;

use App\Models\Quote\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "created" event.
     *
     * @param \App\Models\Quote\Quote $quote
     * @return void
     */
    public function created(Quote $quote)
    {
        //
    }
}
