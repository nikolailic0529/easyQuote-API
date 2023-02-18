<?php

namespace App\Domain\Template\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByVendorName extends Query
{
    /**
     * Apply query to the builder instance.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('vendor_name', $this->value);
    }
}
