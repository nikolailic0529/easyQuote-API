<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByUserFullname extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('user_fullname', $this->value);
    }
}
