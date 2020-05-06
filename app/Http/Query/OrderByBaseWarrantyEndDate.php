<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByBaseWarrantyEndDate extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.base_warranty_end_date", $this->value);
    }
}
