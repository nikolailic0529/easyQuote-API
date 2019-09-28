<?php namespace App\Http\Query\Margin;

use App\Http\Query\Query;

class OrderByQuoteType extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('quote_type', request($this->queryName()));
    }
}
