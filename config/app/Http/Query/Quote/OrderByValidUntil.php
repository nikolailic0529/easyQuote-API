<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByValidUntil extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('customer.valid_until', request($this->queryName()))
            ->setUseTableAlias(true)
            ->setLeftJoin(true);
    }
}
