<?php

namespace App\Models\Data;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Language extends UuidModel implements HasOrderedScope
{
    protected $hidden = [
        'pivot', 'native_name', 'code'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
