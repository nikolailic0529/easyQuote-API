<?php

namespace App\Domain\QuoteFile\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByCountryName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('country.name', $this->value);
    }
}
