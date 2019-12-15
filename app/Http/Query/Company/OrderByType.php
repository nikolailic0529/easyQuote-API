<?php namespace App\Http\Query\Company;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.type", $this->value);
    }
}
