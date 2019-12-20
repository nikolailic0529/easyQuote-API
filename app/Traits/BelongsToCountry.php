<?php

namespace App\Traits;

use App\Models\Data\Country;

trait BelongsToCountry
{
    protected function initializeBelongsToCountry()
    {
        $this->fillable = array_merge($this->fillable, ['country_id']);
    }

    public function country()
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }
}
