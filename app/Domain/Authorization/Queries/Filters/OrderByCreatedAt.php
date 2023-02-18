<?php

namespace App\Domain\Authorization\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByCreatedAt extends Query
{
    protected bool $qualifyColumnName = true;

    public function applyQuery($builder, string $table)
    {
        $column = $this->qualifyColumnName ? "$table.created_at" : 'created_at';

        return $builder->orderBy($column, $this->value);
    }

    public function qualifyColumnName($value = true): self
    {
        return tap($this, function () use ($value) {
            $this->qualifyColumnName = $value;
        });
    }
}
