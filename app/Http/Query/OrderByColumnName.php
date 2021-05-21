<?php

namespace App\Http\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderByColumnName
{
    const DEFAULT_ORDER_DIRECTION = 'asc';

    const REQUEST_KEY_PREFIX = 'order_by_';

    protected Request $request;

    protected string $columnName;

    protected string $queryKey;

    public function __construct(Request $request, string $columnName, string $queryKey = null)
    {
        $this->request = $request;
        $this->columnName = $columnName;
        $this->queryKey = $queryKey ?? $columnName;
    }

    /**
     * @param Builder|BaseBuilder $builder
     * @param \Closure $next
     * @return mixed
     */
    public function handle($builder, \Closure $next)
    {
        if (false === $builder instanceof BaseBuilder && false === $builder instanceof Builder) {
            throw new \RuntimeException(sprintf("Builder parameter must be instance of either %s or %s.", BaseBuilder::class, Builder::class));
        }

        if ($this->request->has($this->getRequestKey())) {
            $builder->orderBy($this->columnName, $this->determineOrderDirection());
        }

        return $next($builder);
    }

    protected function getRequestKey(): string
    {
        return self::REQUEST_KEY_PREFIX.Str::snake($this->queryKey);
    }

    protected function determineOrderDirection(): string
    {
        $valueFromRequest = $this->request->input($this->getRequestKey());

        if (false === is_string($valueFromRequest)) {
            return self::DEFAULT_ORDER_DIRECTION;
        }

        return [
                'asc' => 'asc',
                'desc' => 'desc'
            ][strtolower($valueFromRequest)] ?? self::DEFAULT_ORDER_DIRECTION;
    }
}
