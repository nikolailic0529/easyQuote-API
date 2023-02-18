<?php

namespace App\Domain\Activity\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class CauserId extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->where("{$table}.causer_id", $this->value);
    }
}
