<?php

namespace App\Models;

use App\Traits\{
    Activatable,
    BelongsToCountry,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Arr;

class Address extends UuidModel
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
        'country_id'
    ];

    protected $hidden = [
        'addressable_id', 'addressable_type', 'deleted_at'
    ];

    public function scopeType($query, string $type)
    {
        return $query->whereAddressType($type);
    }

    public function toSearchArray()
    {
        return Arr::except($this->load('country')->toArray(), ['country_id']);
    }
}
