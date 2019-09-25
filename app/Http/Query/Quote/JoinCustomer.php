<?php namespace App\Http\Query\Quote;

use Closure;

class JoinCustomer
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        return $builder->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->select('quotes.*');
    }
}
