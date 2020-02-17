<?php

namespace App\Models\Data;

use App\Models\BaseModel;
use App\Contracts\HasOrderedScope;

class Timezone extends BaseModel implements HasOrderedScope
{
    public $timestamps = false;

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`text`, ?, ?) desc", [TZ_DEF_01, TZ_DEF_02])
            ->orderBy('offset');
    }

    public function getValueAttribute()
    {
        return $this->attributes['text'];
    }
}
