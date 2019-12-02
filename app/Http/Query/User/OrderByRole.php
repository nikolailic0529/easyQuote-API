<?php namespace App\Http\Query\User;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;

class OrderByRole extends Query
{
    public function applyQuery(Builder $builder, string $table)
    {
        $model = get_class($builder->getModel());
        $model_has_roles = uniqid();
        $roles = uniqid();

        return $builder->join("model_has_roles as {$model_has_roles}", function ($join) use ($table, $model, $model_has_roles) {
                $join->on("{$model_has_roles}.model_id", '=', "{$table}.id")
                    ->where("{$model_has_roles}.model_type", '=', $model);
            })
            ->join("roles as {$roles}", "{$roles}.id", '=', "{$model_has_roles}.role_id")
            ->orderBy("{$roles}.name", $this->value)
            ->groupBy("{$table}.id")
            ->select("{$table}.*");
    }
}
