<?php namespace App\Http\Query\Margin;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByValue extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("value", $this->value);
    }
}
