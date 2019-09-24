<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderBySupportEnd extends Query
{
    public function applyQuery($builder)
    {
        return $builder->whereHas('customer', function ($query) {
            return $query->orderBy('support_end', request($this->queryName()));
        });
    }
}
