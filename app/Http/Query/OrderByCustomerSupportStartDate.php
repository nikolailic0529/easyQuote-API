<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByCustomerSupportStartDate extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_support_start_date', $this->value);
    }
}
