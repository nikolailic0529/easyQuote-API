<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCreatedAt extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.created_at", $this->value);
    }
}
