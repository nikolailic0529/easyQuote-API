<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Timezone extends UuidModel implements HasOrderedScope
{
    public function scopeOrdered($query)
    {
        return $query->orderBy('offset', 'desc');
    }
}
