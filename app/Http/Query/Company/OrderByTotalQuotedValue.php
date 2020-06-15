<?php namespace App\Http\Query\Company;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByTotalQuotedValue extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("total_quoted_value", $this->value);
    }
}
