<?php

namespace App\Domain\Rescue\Queries\Filters;

use App\Foundation\Database\Eloquent\QueryFilter\Query;
use Illuminate\Http\Request;

class OrderByCompleteness extends Query
{
    protected ?string $column = '';

    public function __construct(string $column = null, Request $request = null)
    {
        $this->column = $column;

        parent::__construct($request);
    }

    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy($this->column ?? "{$table}.completeness", $this->value);
    }
}
