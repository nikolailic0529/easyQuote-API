<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByQuoteId extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.quote_id", $this->value);
    }
}
