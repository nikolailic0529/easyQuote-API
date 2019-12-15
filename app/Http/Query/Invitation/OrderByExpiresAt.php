<?php namespace App\Http\Query\Invitation;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByExpiresAt extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.expires_at", $this->value);
    }
}
