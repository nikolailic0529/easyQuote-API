<?php

namespace App\Domain\Timezone\Concerns;

use App\Domain\Timezone\Models\Timezone;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTimezone
{
    public function timezone(): BelongsTo
    {
        return $this->belongsTo(Timezone::class)->withDefault();
    }

    public function getTzAttribute(): string
    {
        return cache()->sear('tz:'.$this->timezone_id, fn () => $this->timezone->utc ?? config('app.timezone'));
    }
}
