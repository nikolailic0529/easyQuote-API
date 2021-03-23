<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByOpportunityAmount extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('opportunity_amount', $this->value);
    }
}
