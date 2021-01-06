<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCreatedAt extends Query
{
    protected bool $shallQualify = true;

    public function applyQuery($builder, string $table)
    {
        $column = $this->shallQualify ? "$table.created_at" : 'created_at';

        return $builder->orderBy($column, $this->value);
    }

    public function shallQualify($value = true)
    {
        $this->shallQualify = $value;

        return $this;
    }
}
