<?php namespace App\Http\Query\Discount;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByDurationsDuration extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("json_unquote(json_extract(`durations`, '$**.\"duration\"')) {$this->value}");
    }
}
