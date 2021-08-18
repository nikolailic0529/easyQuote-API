<?php

namespace App\Http\Query\Activity;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class FilterActivityByDescriptionPipe implements RequestQueryBuilderPipe
{
    /**
     * @inheritDoc
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $filterValues = array_values(array_filter(Arr::wrap($request->input('types')), 'is_string'));

        if (empty($filterValues)) {
            return;
        }

        $builder->where(function (Builder $builder) use ($filterValues) {
            $builder->whereIn($builder->qualifyColumn('description'), $filterValues);
        });
    }
}