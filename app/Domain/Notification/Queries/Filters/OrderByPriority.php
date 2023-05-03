<?php

namespace App\Domain\Notification\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByPriority extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.priority", $this->value);
    }
}
