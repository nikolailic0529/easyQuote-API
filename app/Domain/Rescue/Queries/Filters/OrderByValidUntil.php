<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByValidUntil extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('customer_valid_until_date', $this->value);
    }
}
