<?php namespace App\Http\Query\Vendor;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByShortCode extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.short_code", $this->value);
    }
}
