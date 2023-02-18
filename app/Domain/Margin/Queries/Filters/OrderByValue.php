<?php

namespace App\Domain\Margin\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByValue extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('value', $this->value);
    }
}
