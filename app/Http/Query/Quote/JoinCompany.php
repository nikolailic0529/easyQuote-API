<?php namespace App\Http\Query\Quote;

use Closure;

class JoinCompany
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        return $builder->leftJoin('companies', 'companies.id', '=', 'quotes.company_id')
            ->select('quotes.*');
    }
}
