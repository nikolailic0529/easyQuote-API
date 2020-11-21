<?php

namespace App\Models\Data;

use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
use App\Traits\{
    Activatable,
    Systemable,
    Auth\Multitenantable,
    Search\Searchable,
    Activity\LogsActivity,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Relations\BelongsTo,
    SoftDeletes
};
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Country extends Model implements HasOrderedScope, ActivatableInterface
{
    use Uuid, Multitenantable, Activatable, Systemable, Searchable, LogsActivity, SoftDeletes, HasRelationships;

    const FLAGS_DIRECTORY = 'img/countries';

    protected $fillable = [
        'iso_3166_2', 'name', 'default_currency_id', 'currency_code', 'currency_name', 'currency_symbol', 'user_id'
    ];

    protected $hidden = [
        'pivot', 'iso_3166_3', 'full_name', 'country_code', 'capital', 'citizenship', 'calling_code', 'laravel_through_key', 'default_country_id'
    ];

    protected static $logAttributes = [
        'name', 'iso_3166_2', 'currency_code', 'currency_name', 'currency_symbol', 'defaultCurrency.code'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

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

    public function getFlagAttribute($flag)
    {
        return !is_null($flag)
            ? asset(static::FLAGS_DIRECTORY . '/' . $flag)
            : null;
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public function toSearchArray()
    {
        return [
            'name'              => $this->name,
            'iso_3166_2'        => $this->iso_3166_2,
            'currency_code'     => $this->currency_code,
            'currency_name'     => $this->currency_name,
            'currency_symbol'   => $this->currency_symbol,
            'created_at'        => $this->created_at
        ];
    }
}
