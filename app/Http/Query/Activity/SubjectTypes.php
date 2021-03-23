<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class SubjectTypes extends Query
{
    public function applyQuery($builder, string $table)
    {
        if (blank($this->value)) {
            return $builder;
        }

        $subjects = collect(config('activitylog.subject_types'))->only(Arr::wrap($this->value))->flatten()->toArray();

        return $builder->whereIn("{$table}.subject_type", $subjects);
    }
}
