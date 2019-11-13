<?php

namespace App\Traits\Margin;

use App\Models\Quote\Margin\CountryMargin;

trait HasCountryMargins
{
    public function countryMargins()
    {
        return $this->hasMany(CountryMargin::class);
    }
}
