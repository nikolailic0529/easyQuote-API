<?php

namespace App\Observers;

use App\Models\Quote\Margin\CountryMargin;

class MarginObserver
{
    /**
     * Handle the CountryMargin "saving" event.
     *
     * @param CountryMargin $countryMargin
     * @return void
     */
    public function saving(CountryMargin $countryMargin)
    {
        //
    }
}
