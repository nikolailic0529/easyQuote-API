<?php namespace App\Http\Query;

use Closure, Str;

abstract class Query
{
    public function handle($request, Closure $next)
    {
        if(!request()->has($this->queryName())) {
            return $next($request);
        }

        if($this->isOrderBy() && $this->isMailformedOrderByQuery()) {
            return $next($request);
        }

        return $this->applyQuery($next($request));
    }

    protected function queryName()
    {
        return Str::snake(class_basename($this));
    }

    protected function isOrderBy()
    {
        return Str::startsWith($this->queryName(), 'order_by');
    }

    protected function isMailformedOrderByQuery()
    {
        return !in_array(request($this->queryName()), ['asc', 'desc']);
    }

    protected abstract function applyQuery($builder);
}
