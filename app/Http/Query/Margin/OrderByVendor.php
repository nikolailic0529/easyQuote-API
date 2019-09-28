<?php namespace App\Http\Query\Margin;

use App\Http\Query\Query;

class OrderByVendor extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('vendors.name', request($this->queryName()));
    }
}
