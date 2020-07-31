<?php namespace App\Http\Query\QuoteTemplate;

use App\Http\Query\Concerns\Query;
use App\Services\BuilderHelper;
use Illuminate\Database\Eloquent\Builder;

class OrderByVendorName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return BuilderHelper::rememberBaseSelect(
            $builder,
            fn ($builder) => $builder->orderByJoin('vendor.name', $this->value)
        );
    }
}
