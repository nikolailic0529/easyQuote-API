<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByCustomerRfqNumber extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_rfq_number', $this->value);
    }
}