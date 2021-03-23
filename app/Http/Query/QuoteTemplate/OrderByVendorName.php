<?php namespace App\Http\Query\QuoteTemplate;

use App\Http\Query\Concerns\Query;
use App\Services\BuilderHelper;
use Illuminate\Database\Eloquent\Builder;

class OrderByVendorName extends Query
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
        return $builder->orderBy('vendor_name', $this->value);
    }
}
