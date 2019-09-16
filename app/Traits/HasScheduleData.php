<?php namespace App\Traits;

use App\Models\QuoteFile\ScheduleData;

trait HasScheduleData
{
    public function scheduleData()
    {
        return $this->hasOne(ScheduleData::class);
    }
}
