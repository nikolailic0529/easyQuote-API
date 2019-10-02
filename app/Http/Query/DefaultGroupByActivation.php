<?php namespace App\Http\Query;

use Closure, DB;

class DefaultGroupByActivation
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);
        $table = $builder->getModel()->getTable();

        return $builder->orderBy("{$table}.activated_at", 'desc');
    }
}
