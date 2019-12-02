<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderBySupportStart extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('customer.support_start', $this->value);
    }
}
