<?php namespace App\Http\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class DefaultOrderBy
{
    protected string $column;

    protected string $ignore;

    public function __construct(string $column = 'created_at', string $ignore = 'activated_at IS NULL')
    {
        $this->column = $column;
        $this->ignore = $ignore;
    }

    /**
     * Handle the builder instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Closure $next
     * @return void
     */
    public function handle($builder, Closure $next)
    {
        $orders = $builder instanceof Builder
            ? $builder->getQuery()->orders
            : $builder->orders;

        $orders = array_filter($orders ?? [], fn ($order) => !isset($order['sql']) || !str_contains($order['sql'], $this->ignore));

        if (!empty($orders)) {
            return $builder;
        }

        // $column = $builder instanceof Builder ? $builder->qualifyColumn($this->column) : $this->column;

        return $next($builder->orderBy($this->column, 'desc'));
    }
}
