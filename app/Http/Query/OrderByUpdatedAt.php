<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByUpdatedAt extends Query
{
    protected bool $qualifyColumnName = true;

    public function applyQuery($builder, string $table)
    {
        $column = $this->qualifyColumnName ? "$table.updated_at" : 'updated_at';

        return $builder->orderBy($column, $this->value);
    }

    public function qualifyColumnName($value = true): self
    {
        return tap($this, function () use ($value) {
            $this->qualifyColumnName = $value;
        });
    }
}
