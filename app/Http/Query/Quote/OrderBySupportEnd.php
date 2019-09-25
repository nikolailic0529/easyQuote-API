<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderBySupportEnd extends Query
{
    public function applyQuery($builder)
    {
        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->orderBy('customers.support_end', request($this->queryName()))
            ->select('quotes.*');
    }
}
