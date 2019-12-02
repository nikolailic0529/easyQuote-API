<?php

namespace App\Traits;

use App\Models\Data\Timezone;

trait BelongsToTimezone
{
    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }
}
