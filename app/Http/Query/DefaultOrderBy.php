<?php namespace App\Http\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class DefaultOrderBy
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        $orders = $builder instanceof Builder
            ? $builder->getQuery()->orders
            : $builder->orders;

        if (filled($orders)) {
            return $builder;
        }

        return $builder->orderBy('created_at', 'desc');
    }
}
