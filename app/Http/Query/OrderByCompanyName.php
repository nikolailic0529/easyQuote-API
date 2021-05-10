<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByCompanyName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('company_name', $this->value);
    }
}