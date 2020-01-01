<?php

namespace App\Traits;

use App\Models\QuoteFile\ScheduleData;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasScheduleData
{
    public function scheduleData(): HasOne
    {
        return $this->hasOne(ScheduleData::class);
    }
}
