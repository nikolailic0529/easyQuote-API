<?php

namespace App\Traits;

use App\Models\Data\Timezone;

trait HasTimezone
{
    public function timezone()
    {
        return $this->hasOne(Timezone::class);
    }
}
