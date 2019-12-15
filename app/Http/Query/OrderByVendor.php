<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByVendor extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('vendor.name', $this->value);
    }
}
