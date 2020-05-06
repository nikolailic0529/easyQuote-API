<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByActiveWarrantyStartDate extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.active_warranty_start_date", $this->value);
    }
}
