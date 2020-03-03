<?php

namespace App\Traits\Margin;

use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCountryMargins
{
    public function countryMargins(): HasMany
    {
        return $this->hasMany(CountryMargin::class);
    }
}
