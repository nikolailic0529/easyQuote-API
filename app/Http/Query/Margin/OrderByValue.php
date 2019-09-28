<?php namespace App\Http\Query\Margin;

use App\Http\Query\Query;

class OrderByValue extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('value', request($this->queryName()));
    }
}
