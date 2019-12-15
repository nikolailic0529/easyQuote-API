<?php namespace App\Http\Query\Invitation;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByRole extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByJoin('role.name', $this->value);
    }
}
