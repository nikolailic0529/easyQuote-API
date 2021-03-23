<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByStatus extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('status', $this->value);
    }
}
