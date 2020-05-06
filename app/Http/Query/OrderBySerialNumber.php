<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderBySerialNumber extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.serial_number", $this->value);
    }
}
