<?php

namespace App\Traits;

use App\Models\Data\Timezone;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasTimezone
{
    public function timezone(): HasOne
    {
        return $this->hasOne(Timezone::class);
    }
}
