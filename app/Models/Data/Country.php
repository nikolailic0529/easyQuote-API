<?php

namespace App\Models\Data;

use App\Models\BaseModel;
use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
use App\Traits\{
    Activatable,
    Systemable,
    Auth\Multitenantable,
    Search\Searchable
};
use Illuminate\Database\Eloquent\{
    Builder,
    Relations\BelongsTo
};

class Country extends BaseModel implements HasOrderedScope, ActivatableInterface
{
    use Multitenantable, Activatable, Systemable, Searchable;

    protected $fillable = [
        'iso_3166_2', 'name', 'default_currency_id', 'currency_code', 'currency_name', 'currency_symbol', 'user_id'
    ];

    protected $hidden = [
        'pivot', 'iso_3166_3', 'full_name', 'country_code', 'capital', 'citizenship', 'calling_code'
    ];

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id')->withDefault();
    }

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
