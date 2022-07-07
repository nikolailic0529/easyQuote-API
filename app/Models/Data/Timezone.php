<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $utc
 */
class Timezone extends Model implements HasOrderedScope
{
    use Uuid;

    public $timestamps = false;

    protected $fillable = [
        'abbr', 'utc', 'text', 'value', 'offset'
    ];

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
