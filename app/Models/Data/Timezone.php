<?php

namespace App\Models\Data;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;

class Timezone extends UuidModel implements HasOrderedScope
{
    public $timestamps = false;

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`text`, '(UTC+01:00) Edinburgh, London', '(UTC) Edinburgh, London') desc")
            ->orderBy('offset');
    }

    public function getValueAttribute()
    {
        return $this->attributes['text'];
    }
}
