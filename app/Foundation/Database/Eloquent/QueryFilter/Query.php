<?php

namespace App\Foundation\Database\Eloquent\QueryFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @property string $value
 */
abstract class Query
{
    protected Request $request;

    public function __construct(Request $request = null)
    {
        $this->request ??= app()['request'];
    }

    public function handle($request, \Closure $next)
    {
        if (!$this->request->has($this->queryName())) {
            return $next($request);
        }

        if ($this->isOrderBy() && $this->isMailformedOrderByQuery()) {
            return $next($request);
        }

        return $next($this->applyQuery($request, $this->modelTable($request)));
    }

    public function __get($key)
    {
        $getter = 'get'.ucfirst($key);

        if (!method_exists($this, $getter)) {
            return;
        }

        return $this->{$getter}();
    }

    public function getValue()
    {
        return $this->request->input($this->queryName());
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

    protected function modelTable($builder)
    {
        return $builder instanceof Builder
            ? $builder->getModel()->getTable()
            : $builder->from;
    }

    abstract protected function applyQuery($builder, string $table);
}
