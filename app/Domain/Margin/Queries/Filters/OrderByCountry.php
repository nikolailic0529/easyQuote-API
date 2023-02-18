<?php

namespace App\Domain\Margin\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByCountry extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('country.name', $this->value);
    }
}
