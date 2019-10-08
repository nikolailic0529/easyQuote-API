<?php namespace App\Repositories;

use App\Models\Data\Timezone;
use App\Contracts\Repositories\TimezoneRepositoryInterface;
use Cache;

class TimezoneRepository implements TimezoneRepositoryInterface
{
    protected $timezone;

    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function all()
    {
        return Cache::rememberForever('all-timezones', function () {
            return $this->timezone->ordered()->get();
        });
    }
}
