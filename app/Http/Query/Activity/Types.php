<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class Types extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        if (blank($this->value)) {
            return $builder;
        }

        return $builder->whereIn("{$table}.description", Arr::wrap($this->value));
    }
}
