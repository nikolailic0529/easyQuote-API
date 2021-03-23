<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByOpportunityType extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('opportunity_type', $this->value);
    }
}
