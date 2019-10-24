<?php namespace App\Traits\Discount;

use DB;

trait HasDurationsAttribute
{
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
        return collect($this->durations)->pluck('duration')->toArray();
    }
}
