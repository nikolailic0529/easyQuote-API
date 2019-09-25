<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByRfq extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('customers.rfq', request($this->queryName()));
    }
}
