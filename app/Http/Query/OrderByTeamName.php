<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByTeamName extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("team_name", $this->value);
    }
}
