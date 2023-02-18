<?php

namespace App\Domain\Rescue\Queries\Filters;

use Illuminate\Database\Eloquent\Builder;

class ActiveFirst
{
    protected string $column;

    public function __construct($column = 'is_active')
    {
        $this->column = $column;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->orderByDesc($this->column);

        return $next($builder);
    }
}
