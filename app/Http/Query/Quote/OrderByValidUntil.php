<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByValidUntil extends Query
{
    public function applyQuery($builder)
    {
        return $builder->whereHas('customer', function ($query) {
            return $query->orderBy('valid_until', request($this->queryName()));
        });
    }
}
