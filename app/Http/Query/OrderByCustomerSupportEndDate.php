<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByCustomerSupportEndDate extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_support_end_date', $this->value);
    }
}
