<?php

namespace App\Foundation\Support\Mixins;

use App\Foundation\Support\Date\Period;

class CarbonMixin
{
    public function period()
    {
        return function (string $period) {
            return Period::create($period);
        };
    }
}
