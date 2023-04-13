<?php

namespace App\Domain\Timezone\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $utc
 */
class Timezone extends Model implements HasOrderedScope
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];

    public function scopeOrdered($query): Builder
    {
        return $query->orderByRaw('field(`text`, ?, ?) desc', [TZ_DEF_01, TZ_DEF_02])
            ->orderBy('offset');
    }

    public function getValueAttribute()
    {
        return $this->attributes['text'];
    }
}
