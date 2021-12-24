<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByOrderNumber extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('order_number', $this->value);
    }
}
