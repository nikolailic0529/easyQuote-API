<?php namespace App\Http\Query\ImportableColumn;

use App\Http\Query\Concerns\Query;

class OrderByType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.type", $this->value);
    }
}
