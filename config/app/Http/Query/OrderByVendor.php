<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByVendor extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('vendor.name', request($this->queryName()))
            ->setUseTableAlias(true)
            ->setLeftJoin(true);
    }
}
