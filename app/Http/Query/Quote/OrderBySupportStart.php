<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderBySupportStart extends Query
{
    public function applyQuery($builder)
    {
        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->orderBy('customers.support_start', request($this->queryName()))
            ->select('quotes.*');
    }
}
