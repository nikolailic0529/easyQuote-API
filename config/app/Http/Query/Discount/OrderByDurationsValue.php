<?php namespace App\Http\Query\Discount;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByDurationsValue extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        $order = request($this->queryName());
        return $builder->orderByRaw("json_unquote(json_extract(`durations`, '$**.\"value\"')) {$order}");
    }
}
