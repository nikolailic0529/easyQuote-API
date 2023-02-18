<?php

namespace App\Domain\Discount\Repositories;

use App\Domain\Shared\Eloquent\Repository\SearchableRepository;

abstract class DiscountRepository extends SearchableRepository
{
    protected function filterQueryThrough(): array
    {
        $through = [
            \App\Domain\Discount\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Discount\Queries\Filters\OrderByVendor::class,
            \App\Domain\Discount\Queries\Filters\OrderByCountry::class,
            \App\Domain\Discount\Queries\Filters\OrderByName::class,
        ];

        return array_merge($through, $this->appendFilterQueryThrough(), [\App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class]);
    }

    /**
     * Append Filter Query classes.
     */
    abstract protected function appendFilterQueryThrough(): array;
}
