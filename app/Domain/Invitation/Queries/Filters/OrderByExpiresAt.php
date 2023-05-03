<?php

namespace App\Domain\Invitation\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByExpiresAt extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.expires_at", $this->value);
    }
}
