<?php namespace App\Http\Query\ImportableColumn;

use App\Http\Query\Concerns\Query;

class OrderByCountryName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin("country.name", $this->value);
    }
}
