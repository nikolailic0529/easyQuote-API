<?php

namespace App\Traits;

trait HasTimezone
{
    public function timezone()
    {
        return $this->hasOne(App\Timezone::class);
    }
}