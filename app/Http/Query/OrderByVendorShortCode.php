<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByVendorShortCode extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.vendor_short_code", $this->value);
    }
}
