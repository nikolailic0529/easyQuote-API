<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends UuidModel
{
    use SoftDeletes;

    protected $fillable = [
        'address_type',
        'address_1',
        'address_2',
        'city',
        'state',
        'state_code',
        'post_code',
        'country_code'
    ];

    protected $hidden = [
        'addressable_id', 'addressable_type', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
