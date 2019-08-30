<?php

namespace App\Models\Data;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Currency extends UuidModel implements HasOrderedScope
{
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
