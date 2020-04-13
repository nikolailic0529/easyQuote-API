<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\{
    Eloquent\Builder as EloquentBuilder,
    Query\Builder,
};
use Illuminate\Pipeline\Pipeline;
use Closure;

trait FiltersQuery
{
    protected function filterQuery($query, ?Closure $scope = null)
    {
        if (!$query instanceof Builder && !$query instanceof EloquentBuilder) {
            throw new \Exception(
                sprintf(
                    'Argument passed to %s::%s() method must be an instance of %s or %s',
                    __CLASS__,
                    __FUNCTION__,
                    Builder::class,
                    EloquentBuilder::class
                )
            );
        }

        return app(Pipeline::class)
            ->send($query)
            ->through($this->filterQueryThrough())
            ->then(
                fn ($passable) => tap($passable, fn ($passable) => is_callable($scope) && $scope($passable))
            );
    }

    /**
     * Filter Query over Classes Array.
     *
     * @return array
     */
    abstract protected function filterQueryThrough(): array;
}
