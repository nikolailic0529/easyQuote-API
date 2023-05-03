<?php

namespace App\Domain\Vendor\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByShortCode extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.short_code", $this->value);
    }
}
