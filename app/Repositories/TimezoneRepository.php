<?php

namespace App\Repositories;

use App\Contracts\Repositories\TimezoneRepositoryInterface;
use App\Models\Data\Timezone;

class TimezoneRepository implements TimezoneRepositoryInterface
{
    /** @var \App\Models\Data\Timezone */
    protected Timezone $timezone;

    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function all()
    {
        return cache()->sear('all-timezones', fn () => $this->timezone->ordered()->get(['id', 'text', 'value']));
    }

    public function random(): Timezone
    {
        return $this->timezone->query()->inRandomOrder()->first();
    }
}
