<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByRfq extends Query
{
    public function applyQuery($builder)
    {
        return $builder->whereHas('customer', function ($query) {
            return $query->orderBy('rfq', request($this->queryName()));
        });
    }
}
