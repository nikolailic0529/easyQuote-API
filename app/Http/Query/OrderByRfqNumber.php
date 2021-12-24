<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByRfqNumber extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('rfq_number', $this->value);
    }
}