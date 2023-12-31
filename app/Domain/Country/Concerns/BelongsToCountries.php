<?php

namespace App\Domain\Country\Concerns;

use App\Domain\Country\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

trait BelongsToCountries
{
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class)->orderBy('name');
    }

    public function syncCountries(?array $countries): void
    {
        if (blank($countries)) {
            return;
        }

        $oldCountries = $this->countries;

        $changes = $this->countries()->sync($countries);

        if (blank(Arr::flatten($changes))) {
            return;
        }

        $this->fireModelEvent('saved', false);

        $newCountries = $this->load('countries')->countries;

        activity()
            ->on($this)
            ->withAttribute('countries', $newCountries->toString('name'), $oldCountries->toString('name'))
            ->queue('updated');
    }

    public function scopeCountry(Builder $query, string $id): Builder
    {
        return $query->whereHas('countries', function ($query) use ($id) {
            $query->where('countries.id', $id);
        });
    }
}
