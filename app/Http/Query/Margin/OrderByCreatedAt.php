<?php namespace App\Http\Query\Margin;

use App\Http\Query\Query;

class OrderByCreatedAt extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('created_at', request($this->queryName()));
    }
}
