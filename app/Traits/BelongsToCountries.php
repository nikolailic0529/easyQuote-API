<?php namespace App\Traits;

use App\Models\Data\Country;

trait BelongsToCountries
{
    public function countries()
    {
        return $this->belongsToMany(Country::class);
    }
}