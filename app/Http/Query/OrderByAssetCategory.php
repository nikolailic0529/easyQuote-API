<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByAssetCategory extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.asset_category_id", $this->value);
    }
}
