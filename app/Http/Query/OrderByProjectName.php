<?php

namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByProjectName extends Query
{
    protected function applyQuery($builder, string $table)
    {
        return $builder->orderBy('project_name', $this->value);
    }
}
