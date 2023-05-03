<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderBySupportEnd extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_support_end_date', $this->value);
    }
}
