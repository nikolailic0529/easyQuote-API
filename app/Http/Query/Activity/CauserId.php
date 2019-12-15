<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class CauserId extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->where("{$table}.causer_id", $this->value);
    }
}
