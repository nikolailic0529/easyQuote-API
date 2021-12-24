<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByTypeName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('type_name', $this->value);
    }
}
