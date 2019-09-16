<?php namespace App\Models\Customer;

use App\Models\UuidModel;

class Customer extends UuidModel
{
    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y',
        'valid_until' => 'datetime:d/m/Y',
        'support_start' => 'datetime:d/m/Y',
        'support_end' => 'datetime:d/m/Y'
    ];
}
