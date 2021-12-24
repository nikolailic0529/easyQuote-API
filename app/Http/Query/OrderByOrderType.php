<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByOrderType extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('order_type', $this->value);
    }
}
