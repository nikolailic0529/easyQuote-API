<?php

namespace App\Domain\User\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByFirstname extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.first_name", $this->value);
    }
}
