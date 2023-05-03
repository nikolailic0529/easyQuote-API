<?php

namespace App\Domain\Contact\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByIsVerified extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.is_verified", $this->value);
    }
}
