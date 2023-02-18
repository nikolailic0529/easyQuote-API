<?php

namespace App\Domain\User\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;

class OrderByName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy('user_fullname', $this->value);
    }
}
