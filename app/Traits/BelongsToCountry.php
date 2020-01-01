<?php

namespace App\Traits;

use App\Models\Data\Country;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCountry
{
    protected function initializeBelongsToCountry()
    {
        $this->fillable = array_merge($this->fillable, ['country_id']);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }
}
