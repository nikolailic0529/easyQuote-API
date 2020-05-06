<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderBySku extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.sku", $this->value);
    }
}
