<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderBySupportStart extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('customers.support_start', request($this->queryName()));
    }
}
