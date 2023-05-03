<?php

namespace App\Domain\Activity\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class Types extends Query
{
    public function applyQuery($builder, string $table)
    {
        if (blank($this->value)) {
            return $builder;
        }

        return $builder->whereIn("{$table}.description", \Arr::wrap($this->value));
    }
}
