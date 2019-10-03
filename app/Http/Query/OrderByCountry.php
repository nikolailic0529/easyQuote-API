<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCountry extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('country.name', request($this->queryName()));
    }
}
