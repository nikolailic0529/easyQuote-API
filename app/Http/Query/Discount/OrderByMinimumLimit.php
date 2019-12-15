<?php namespace App\Http\Query\Discount;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByMinimumLimit extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.minimum_limit", $this->value);
    }
}
