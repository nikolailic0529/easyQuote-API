<?php

namespace App\Domain\Invitation\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByRole extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('role.name', $this->value);
    }
}
