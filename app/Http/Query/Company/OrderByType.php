<?php namespace App\Http\Query\Company;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByType extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderBy("{$table}.type", request($this->queryName()));
    }
}
