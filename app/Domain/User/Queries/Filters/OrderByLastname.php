<?php

namespace App\Domain\User\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByLastname extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.last_name", $this->value);
    }
}
