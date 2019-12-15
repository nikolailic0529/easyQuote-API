<?php namespace App\Http\Query\Contact;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByFirstName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("{$table}.first_name", $this->value);
    }
}
