<?php

namespace App\Domain\Margin\Concerns;

use App\Domain\Margin\Models\CountryMargin;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCountryMargins
{
    public function countryMargins(): HasMany
    {
        return $this->hasMany(CountryMargin::class);
    }
}
