<?php

namespace App\Domain\Company\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByVat extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.vat", $this->value);
    }
}
