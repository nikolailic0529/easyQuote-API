<?php

namespace App\Domain\Discount\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByDurationsValue extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("cast(json_unquote(json_extract(`durations`, '$.\"value\".\"value\"')) as float) $this->value");
    }
}
