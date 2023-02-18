<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByCompanyName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('company_name', $this->value);
    }
}
