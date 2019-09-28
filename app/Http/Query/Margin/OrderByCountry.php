<?php namespace App\Http\Query\Margin;

use App\Http\Query\Query;

class OrderByCountry extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('countries.name', request($this->queryName()));
    }
}
