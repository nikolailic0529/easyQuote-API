<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderBySaleActionName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('sale_action_name', $this->value);
    }
}
