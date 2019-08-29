<?php

namespace App\Repositories;

use App\Models\Timezone;
use App\Contracts\Repositories\TimezoneRepositoryInterface;

class TimezoneRepository implements TimezoneRepositoryInterface
{
    public function all()
    {
        return Timezone::ordered()->get();
    }
}