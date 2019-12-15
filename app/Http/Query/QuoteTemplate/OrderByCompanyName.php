<?php namespace App\Http\Query\QuoteTemplate;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByCompanyName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('company.name', $this->value);
    }
}
