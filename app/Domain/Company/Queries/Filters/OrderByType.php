<?php

namespace App\Domain\Company\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.type", $this->value);
    }
}
