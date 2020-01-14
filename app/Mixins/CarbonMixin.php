<?php

namespace App\Mixins;

use App\Models\System\Period;

class CarbonMixin
{
    public function period()
    {
        return function (string $period) {
            return Period::create($period);
        };
    }
}
