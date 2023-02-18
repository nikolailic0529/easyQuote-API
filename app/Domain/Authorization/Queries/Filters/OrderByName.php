<?php

namespace App\Domain\Authorization\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.name", $this->value);
    }
}
