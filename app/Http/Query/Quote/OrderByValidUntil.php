<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByValidUntil extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("STR_TO_DATE(JSON_UNQUOTE(`cached_relations`->'$.customer.valid_until_date'), '%Y-%m-%d') {$this->value}");
    }
}
