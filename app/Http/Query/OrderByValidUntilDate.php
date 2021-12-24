<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByValidUntilDate extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('valid_until_date', $this->value);
    }
}