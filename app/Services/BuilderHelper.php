<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BuilderHelper
{
    /**
     * Remember initial selected model columns and replace after callback.
     *
     * @param Builder $builder
     * @param Closure $closure
     * @return Builder
     */
    public static function rememberBaseSelect(Builder $builder, Closure $closure)
    {
        $baseSelect = $builder->getModel()->getTable() . '.*';

        $rememberedSelect = $builder->getQuery()->columns ?? $baseSelect;

        call_user_func($closure, $builder);

        $builder->getQuery()->columns = Collection::wrap($builder->getQuery()->columns)
            ->reduce(function (array $columns, $column) use ($rememberedSelect, $baseSelect) {
                if (!$column instanceof Expression || $column->getValue() !== $baseSelect) {
                    return array_merge($columns, Arr::wrap($column));
                }

                return array_merge(Arr::wrap($rememberedSelect));
            }, []);

        return $builder;
    }
}
