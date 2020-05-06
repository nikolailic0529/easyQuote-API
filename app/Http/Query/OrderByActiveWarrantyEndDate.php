<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByActiveWarrantyEndDate extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.active_warranty_end_date", $this->value);
    }
}
