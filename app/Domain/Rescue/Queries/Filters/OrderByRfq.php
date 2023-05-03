<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByRfq extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_rfq_number', $this->value);
    }
}
