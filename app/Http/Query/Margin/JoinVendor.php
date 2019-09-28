<?php namespace App\Http\Query\Margin;

use Closure;

class JoinVendor
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);
        $table = $builder->getModel()->getTable();

        return $builder->join('vendors', 'vendors.id', '=', "{$table}.vendor_id")
            ->select("{$table}.*");
    }
}
