<?php

namespace App\Traits;

use App\Models\Data\Timezone;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTimezone
{
    public function timezone(): BelongsTo
    {
        return $this->belongsTo(Timezone::class);
    }
}
