<?php namespace App\Http\Query\Quote;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;
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
