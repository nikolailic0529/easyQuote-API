<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByRfq extends Query
{
    public function applyQuery($builder)
    {
        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->orderBy('customers.rfq', request($this->queryName()))
            ->select('quotes.*');
    }
}
