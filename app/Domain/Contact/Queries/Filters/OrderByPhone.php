<?php

namespace App\Domain\Contact\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByPhone extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.phone", $this->value);
    }
}
