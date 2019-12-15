<?php namespace App\Http\Query\Address;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByState extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.state", $this->value);
    }
}
