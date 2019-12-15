<?php namespace App\Http\Query\User;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderByRaw("concat(`{$table}`.`first_name`, `{$table}`.`middle_name`, `{$table}`.`last_name`) {$this->value}");
    }
}
