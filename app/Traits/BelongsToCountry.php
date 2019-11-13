<?php

namespace App\Traits;

use App\Models\Data\Country;

trait BelongsToCountry
{
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
