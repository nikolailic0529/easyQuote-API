<?php namespace App\Http\Query;

use Closure, Str;

class DefaultOrderBy
{
    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        return $builder->orderBy('created_at', 'desc');
    }
}