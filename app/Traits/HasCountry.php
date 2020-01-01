<?php

namespace App\Traits;

use App\Models\Data\Country;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasCountry
{
    public function country(): HasOne
    {
        return $this->hasOne(Country::class);
    }
}
