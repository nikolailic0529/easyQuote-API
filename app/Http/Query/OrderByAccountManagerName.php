<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByAccountManagerName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('account_manager_name', $this->value);
    }
}
