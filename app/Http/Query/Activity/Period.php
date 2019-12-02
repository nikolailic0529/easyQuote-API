<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class Period extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        if (!in_array($this->value, __('activity.periods'))) {
            return $builder;
        }

        $period = now()->period($this->value);
        $from = $period->from->toDateTimeString();
        $till = $period->till->toDateTimeString();

        return $builder->where("{$table}.created_at", '>=', $from)
            ->where("{$table}.created_at", '<=', $till);
    }
}
