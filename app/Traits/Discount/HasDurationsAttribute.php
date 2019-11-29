<?php

namespace App\Traits\Discount;

use DB;

trait HasDurationsAttribute
{
    public function initializeHasDurationsAttribute()
    {
        $this->fillable = array_merge($this->fillable, ['durations']);
        $this->casts = array_merge($this->casts, ['durations' => 'collection']);
    }

    public function scopeDuration($query, $duration, bool $or = false)
    {
        $duration = (int) $duration;
        $where = $or ? 'orWhereJsonContains' : 'whereJsonContains';

        return $query->{$where}('durations', DB::raw("json_object('duration', '{$duration}')"));
    }

    public function scopeDurationIn($query, array $durations)
    {
        return $query->where(function ($query) use ($durations) {
            $this->scopeDuration($query, array_shift($durations));

            collect($durations)->each(function ($duration) use ($query) {
                $this->scopeDuration($query, $duration, true);
            });
        });
    }

    public function getYearsAttribute()
    {
        return $this->durations->pluck('duration')->toArray();
    }

    public function setDurationsAttribute(array $durations)
    {
        $this->attributes['durations'] = collect($durations)->transform(function ($duration) {
            $duration['value'] = $this->asDecimal($duration['value'], 2);
            return $duration;
        })->toJson();
    }

    public function getDurationAttribute()
    {
        return data_get($this->durations->first(), 'duration');
    }

    public function getValueAttribute()
    {
        return data_get($this->durations->first(), 'value');
    }
}
