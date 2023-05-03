<?php

namespace App\Domain\QuoteFile\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByHeader extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.header", $this->value);
    }
}
