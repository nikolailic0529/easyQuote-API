<?php

namespace App\Http\Query\Activity;

use App\Http\Query\Concerns\Query;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SubjectTypes extends Query
{
    private Config $config;

    public function __construct(Config $config, Request $request = null)
    {
        $this->config = $config;

        parent::__construct($request);
    }

    public function applyQuery($builder, string $table)
    {
        if (blank($this->value)) {
            return $builder;
        }

        $subjectTypes = $this->config->get('activitylog.subject_types', []);

        $subjectTypes = Arr::flatten(Arr::only($subjectTypes, Arr::wrap($this->value)));

        $morphMap = array_flip(Relation::$morphMap);

        $subjectTypes = array_map(function (string $className) use ($morphMap) {
            return $morphMap[$className] ?? $className;
        }, $subjectTypes);

        return $builder->whereIn("{$table}.subject_type", $subjectTypes);
    }
}
