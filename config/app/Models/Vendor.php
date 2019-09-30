<?php namespace App\Models;

use App\Models\UuidModel;
use App\Traits\BelongsToCountries;

class Vendor extends UuidModel
{
    use BelongsToCountries;

    protected $hidden = [
        'pivot', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'is_system'
    ];
}
