<?php namespace App\Http\Query\Margin;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByQuoteType extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('quote_type', $this->value);
    }
}
