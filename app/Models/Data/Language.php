<?php

namespace App\Models\Data;

use App\Models\BaseModel;
use App\Contracts\HasOrderedScope;

class Language extends BaseModel implements HasOrderedScope
{
    protected $hidden = [
        'pivot', 'native_name', 'code'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
