<?php namespace App\Traits;

use App\Models\Data\Country;

trait BelongsToCountries
{
    public function countries()
    {
        return $this->belongsToMany(Country::class);
    }

    public function syncCountries($countries)
    {
        if(!is_array($countries)) {
            return false;
        }

        return $this->countries()->sync($countries);
    }

    public function scopeCountry($query, string $id)
    {
        return $query->whereHas('countries', function ($query) use ($id) {
            $query->where('countries.id', $id);
        });
    }
}
