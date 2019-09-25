<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByCompleteness extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('completeness', request($this->queryName()));
    }
}
