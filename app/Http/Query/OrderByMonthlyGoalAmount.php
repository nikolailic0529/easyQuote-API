<?php namespace App\Http\Query;

use App\Http\Query\Concerns\Query;

class OrderByMonthlyGoalAmount extends Query
{
    public function applyQuery($builder, string $table)
    {
        return $builder->orderBy("monthly_goal_amount", $this->value);
    }
}
