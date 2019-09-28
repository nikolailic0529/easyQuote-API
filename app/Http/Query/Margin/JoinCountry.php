<?php namespace App\Http\Query\Margin;

use Closure;

class JoinCountry
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);
        $table = $builder->getModel()->getTable();

        return $builder->join('countries', 'countries.id', '=', "{$table}.country_id")
            ->select("{$table}.*");
    }
}
