<?php

namespace App\Domain\Activity\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class Period extends Query
{
    public function applyQuery($builder, string $table)
    {
        if (!in_array($this->value, config('activitylog.periods'))) {
            return $builder;
        }

        $period = now()->period($this->value);
        $from = $period->from->toDateTimeString();
        $till = $period->till->toDateTimeString();

        return $builder->where("{$table}.created_at", '>=', $from)
            ->where("{$table}.created_at", '<=', $till);
    }
}
