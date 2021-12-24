<?php

namespace App\Http\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class DefaultOrderBy
{
    protected string $column;

    protected string $ignoreColumn;

    public function __construct(string $column = 'created_at', string $ignoreColumn = 'is_active')
    {
        $this->column = $column;
        $this->ignoreColumn = $ignoreColumn;
    }

    /**
     * Handle the builder instance.
     *
     * @param Builder $builder
     * @param Closure $next
     * @return Builder
     */
    public function handle(Builder $builder, Closure $next)
    {
        $orders = array_filter($builder->getQuery()->orders ?? [], function (array $order) {
            return !isset($order['column']) || $order['column'] !== $this->ignoreColumn;
        });

        if (empty($orders)) {
            $builder->orderBy($this->column, 'desc');
        }

        return $next($builder);
    }
}
