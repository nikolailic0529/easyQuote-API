<?php namespace App\Http\Query\Invitation;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByEmail extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderBy("{$table}.email", request($this->queryName()));
    }
}
