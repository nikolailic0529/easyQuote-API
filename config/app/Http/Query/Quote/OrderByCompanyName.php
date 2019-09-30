<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCompanyName extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('company.name', request($this->queryName()))
            ->setUseTableAlias(true)
            ->setLeftJoin(true);
    }
}
