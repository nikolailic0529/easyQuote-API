<?php namespace App\Http\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class DefaultOrderBy
{
    protected string $column;

    public function __construct(string $column = 'created_at')
    {
        $this->column = $column;
    }

    public function handle($request, Closure $next)
    {
        $builder = $next($request);

        $orders = $builder instanceof Builder
            ? $builder->getQuery()->orders
            : $builder->orders;

        if (filled($orders)) {
            return $builder;
        }

        return $builder->orderBy($this->column, 'desc');
    }
}
