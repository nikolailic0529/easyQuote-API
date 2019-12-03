<?php namespace App\Http\Query\Contact;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByIsVerified extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        return $builder->orderBy("{$table}.is_verified", $this->value);
    }
}