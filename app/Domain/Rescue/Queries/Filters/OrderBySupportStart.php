<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderBySupportStart extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_support_start_date', $this->value);
    }
}
