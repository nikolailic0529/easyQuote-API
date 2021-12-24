<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByAccountName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('account_name', $this->value);
    }
}
