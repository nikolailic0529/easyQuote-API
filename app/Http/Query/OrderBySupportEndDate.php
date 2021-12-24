<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderBySupportEndDate extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('support_end_date', $this->value);
    }
}