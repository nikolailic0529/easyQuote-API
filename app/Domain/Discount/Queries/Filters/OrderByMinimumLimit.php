<?php

namespace App\Domain\Discount\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByMinimumLimit extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.minimum_limit", $this->value);
    }
}
