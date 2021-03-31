<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByStatusReason extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('status_reason', $this->value);
    }
}
