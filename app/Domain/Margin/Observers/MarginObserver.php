<?php

namespace App\Domain\Margin\Observers;

use App\Domain\Margin\Models\CountryMargin;

class MarginObserver
{
    /**
     * Handle the CountryMargin "saving" event.
     *
     * @return void
     */
    public function saving(CountryMargin $countryMargin)
    {
    }
}
