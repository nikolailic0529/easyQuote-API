<?php

namespace App\Foundation\Database\Eloquent\QueryFilter;

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
     * @return Builder
     */
    public function handle(Builder $builder, \Closure $next): mixed
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
