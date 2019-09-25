<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByName extends Query
{
    public function applyQuery($builder)
    {
        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->orderBy('customers.name', request($this->queryName()))
            ->select('quotes.*');
    }
}
