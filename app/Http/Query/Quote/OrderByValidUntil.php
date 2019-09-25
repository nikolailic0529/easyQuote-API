<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByValidUntil extends Query
{
    public function applyQuery($builder)
    {
        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->orderBy('customers.valid_until', request($this->queryName()))
            ->select('quotes.*');
    }
}
