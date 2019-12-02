<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class CustomPeriod extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        if (!is_array($this->value) || !Arr::has($this->value, ['from', 'till'])) {
            return $builder;
        }

        ['from' => $from, 'till' => $till] = $this->value;

        $from = now()->createFromFormat('d/m/y', $from)->toDateTimeString();
        $till = now()->createFromFormat('d/m/y', $till)->toDateTimeString();

        return $builder->where("{$table}.created_at", '>=', $from)
            ->where("{$table}.created_at", '<=', $till);
    }
}
