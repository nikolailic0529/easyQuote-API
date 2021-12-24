<?php

namespace App\Repositories\Quote\Discount;

use App\Repositories\SearchableRepository;

abstract class DiscountRepository extends SearchableRepository
{
    protected function filterQueryThrough(): array
    {
        $through = [
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByVendor::class,
            \App\Http\Query\OrderByCountry::class,
            \App\Http\Query\OrderByName::class,
        ];

        return array_merge($through, $this->appendFilterQueryThrough(), [\App\Http\Query\DefaultOrderBy::class]);
    }

    /**
     * Append Filter Query classes
     *
     * @return array
     */
    abstract protected function appendFilterQueryThrough(): array;
}
