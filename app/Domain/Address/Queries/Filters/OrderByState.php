<?php

namespace App\Domain\Address\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByState extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.state", $this->value);
    }
}
