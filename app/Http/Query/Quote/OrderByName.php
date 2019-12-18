<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByCachedRelation('customer.name', $this->value);
    }
}
