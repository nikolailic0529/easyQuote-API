<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderBySupportStartDate extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('support_start_date', $this->value);
    }
}