<?php namespace App\Http\Query\Address;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByPostCode extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.post_code", $this->value);
    }
}
