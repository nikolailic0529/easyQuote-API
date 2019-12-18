<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderBySupportStart extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByCachedRelation('customer.support_start', $this->value);
    }
}
