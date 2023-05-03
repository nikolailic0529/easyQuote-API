<?php

namespace App\Domain\Company\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByTotalQuotedValue extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('total_quoted_value', $this->value);
    }
}
