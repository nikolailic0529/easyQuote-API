<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model implements HasOrderedScope
{
    use Uuid;

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
