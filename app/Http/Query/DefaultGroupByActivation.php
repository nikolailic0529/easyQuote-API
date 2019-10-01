<?php namespace App\Http\Query;

use Closure, DB;

class DefaultGroupByActivation
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);
        $table = $builder->getModel()->getTable();

        return $builder->groupBy(DB::raw("{$table}.activated_at desc"))
            ->orderBy("{$table}.activated_at", 'desc');
    }
}
