<?php

namespace App\Domain\Shared\Eloquent\Repository\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Pipeline\Pipeline;

trait FiltersQuery
{
    protected function filterQuery($query, ?\Closure $scope = null)
    {
        if (!$query instanceof Builder && !$query instanceof EloquentBuilder) {
            throw new \Exception(sprintf('Argument passed to %s method must be an instance of %s or %s', __METHOD__, Builder::class, EloquentBuilder::class));
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
     */
    abstract protected function filterQueryThrough(): array;
}
