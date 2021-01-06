<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByCustomerName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_name', $this->value);
    }
}