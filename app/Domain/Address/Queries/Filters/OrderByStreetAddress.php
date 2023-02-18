<?php

namespace App\Domain\Address\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByStreetAddress extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.address_1", $this->value);
    }
}
