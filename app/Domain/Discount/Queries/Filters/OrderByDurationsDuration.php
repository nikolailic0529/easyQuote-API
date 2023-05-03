<?php

namespace App\Domain\Discount\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByDurationsDuration extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("cast(json_unquote(json_extract(`durations`, '$.\"duration\".\"duration\"')) as float) $this->value");
    }
}
