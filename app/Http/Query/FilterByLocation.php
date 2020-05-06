<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class FilterByLocation extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->whereHas('location', fn (Builder $q) => $q->whereKey($this->value));
    }
}
