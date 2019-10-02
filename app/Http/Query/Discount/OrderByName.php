<?php namespace App\Http\Query\Discount;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByName extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderBy("{$table}.name", request($this->queryName()));
    }
}