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
        return [
            'city'          => $this->city,
            'country_name'  => $this->country->name,
            'state'         => $this->state,
            'address_type'  => $this->address_type,
            'address_1'     => $this->address_1,
            'address_2'     => $this->address_2,
            'state_code'    => $this->state_code,
            'post_code'     => $this->post_code,
            'created_at'    => $this->created_at
        ];
    }
}
