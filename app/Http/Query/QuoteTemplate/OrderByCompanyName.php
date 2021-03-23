<?php

namespace App\Http\Query\QuoteTemplate;

use App\Http\Query\Concerns\Query;
use App\Services\BuilderHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderByCompanyName extends Query
{
    /**
     * Apply query to the builder instance.
     *
     * @param Builder $builder
     * @param string $table
     * @return Builder
     */
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('company_name', $this->value);
    }
}
