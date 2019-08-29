<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Language extends UuidModel implements HasOrderedScope
{
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
}
