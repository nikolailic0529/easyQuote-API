<?php namespace App\Models;

use App\Models\UuidModel;
use App\Traits \ {
    BelongsToVendors
};

class Company extends UuidModel
{
    use BelongsToVendors;

    protected $hidden = [
        'pivot', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'is_system'
    ];
}