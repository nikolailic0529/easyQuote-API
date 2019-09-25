<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByName extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('customers.name', request($this->queryName()));
    }
}
