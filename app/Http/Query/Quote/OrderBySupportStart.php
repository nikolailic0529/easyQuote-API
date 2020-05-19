<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderBySupportStart extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("STR_TO_DATE(JSON_UNQUOTE(`cached_relations`->'$.customer.support_start_date'), '%Y-%m-%d') {$this->value}");
    }
}
