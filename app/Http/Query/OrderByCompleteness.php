<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCompleteness extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('completeness', $this->value);
    }
}
