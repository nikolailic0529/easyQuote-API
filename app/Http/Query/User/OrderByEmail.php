<?php namespace App\Http\Query\User;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByEmail extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.email", $this->value);
    }
}
