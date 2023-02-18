<?php

namespace App\Domain\Discount\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByVendor extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('vendor.name', $this->value);
    }
}
