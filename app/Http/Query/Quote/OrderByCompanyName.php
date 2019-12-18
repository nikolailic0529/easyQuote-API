<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCompanyName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByCachedRelation('company.name', $this->value);
    }
}
