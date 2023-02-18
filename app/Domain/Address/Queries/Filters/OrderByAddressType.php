<?php

namespace App\Domain\Address\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByAddressType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.address_type", $this->value);
    }
}
