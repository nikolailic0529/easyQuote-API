<?php namespace App\Http\Query\Invitation;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByRole extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderByJoin('role.name', request($this->queryName()));
    }
}