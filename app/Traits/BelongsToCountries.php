<?php

namespace App\Traits;

use App\Models\Data\Country;
use Arr;

trait BelongsToCountries
{
    public function countries()
    {
        return $this->belongsToMany(Country::class)->orderBy('name');
    }

    public function syncCountries($countries)
    {
        if (!is_array($countries)) {
            return false;
        }

        $oldCountries = $this->countries;

        $changes = $this->countries()->sync($countries);

        if (blank(Arr::flatten($changes))) {
            return $changes;
        }

        $newCountries = $this->load('countries')->countries;

        activity()
            ->on($this)
            ->withAttribute('countries', $newCountries->toString('name'), $oldCountries->toString('name'))
            ->log('updated');
    }

    public function scopeCountry($query, string $id)
    {
        return $query->whereHas('countries', function ($query) use ($id) {
            $query->where('countries.id', $id);
        });
    }
}
