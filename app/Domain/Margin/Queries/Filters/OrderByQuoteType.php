<?php

namespace App\Domain\Margin\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByQuoteType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('quote_type', $this->value);
    }
}
