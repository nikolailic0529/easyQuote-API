<?php namespace App\Repositories;

use App\Models\Data\Timezone;
use App\Contracts\Repositories\TimezoneRepositoryInterface;

class TimezoneRepository implements TimezoneRepositoryInterface
{
    protected $timezone;

    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function all()
    {
        return $this->timezone->ordered()->get();
    }
}
