<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByBaseWarrantyStartDate extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.base_warranty_start_date", $this->value);
    }
}
