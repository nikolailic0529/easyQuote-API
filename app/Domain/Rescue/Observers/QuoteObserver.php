<?php

namespace App\Domain\Rescue\Observers;

use App\Domain\Rescue\Models\Quote;

class QuoteObserver
{
    /**
     * Handle the Quote "created" event.
     *
     * @return void
     */
    public function created(Quote $quote)
    {
    }
}
