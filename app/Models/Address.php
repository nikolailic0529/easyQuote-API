<?php

namespace App\Models;

use App\Traits\{
    Activatable,
    BelongsToCountry,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class Address extends BaseModel
{
    use SoftDeletes, Activatable, BelongsToCountry, Searchable;

    protected $fillable = [
        'address_type',
        'address_1',
        'address_2',
        'city',
        'state',
        'state_code',
        'post_code',
        'contact_name',
        'contact_number',
        'country_id'
    ];

    protected $hidden = [
        'addressable_id', 'addressable_type', 'deleted_at', 'pivot'
    ];

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->whereAddressType($type);
    }

    public function scopeCommonTypes(Builder $query): Builder
    {
        return $query->whereIn('address_type', __('address.types'));
    }

    public function toSearchArray()
    {
        return Arr::except($this->load('country')->toArray(), ['country_id']);
    }
}
