<?php namespace App\Http\Query\Quote;

use App\Http\Query\Query;

class OrderByCompanyName extends Query
{
    public function applyQuery($builder)
    {
        return $builder->orderBy('companies.name', request($this->queryName()));
    }
}
