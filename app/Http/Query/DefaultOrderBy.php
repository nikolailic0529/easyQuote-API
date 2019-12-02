<?php namespace App\Http\Query;

use Closure;

class DefaultOrderBy
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        if (filled($builder->getQuery()->orders)) {
            return $builder;
        }

        return $builder->orderBy('created_at', 'desc');
    }
}
