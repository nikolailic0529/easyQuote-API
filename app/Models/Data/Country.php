<?php

namespace App\Models\Data;

use App\Models\{
    UuidModel
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\Builder;

class Country extends UuidModel implements HasOrderedScope
{
    protected $hidden = [
        'pivot', 'iso_3166_3', 'full_name', 'country_code', 'capital', 'citizenship', 'calling_code'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('iso_3166_2', $code);
    }

    public function getCodeAttribute()
    {
        return $this->iso_3166_2;
    }
}
