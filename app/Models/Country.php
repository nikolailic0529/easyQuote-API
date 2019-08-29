<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Country extends UuidModel implements HasOrderedScope
{
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
