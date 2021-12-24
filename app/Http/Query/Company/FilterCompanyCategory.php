<?php

namespace App\Http\Query\Company;

use App\Enum\CompanyCategory;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class FilterCompanyCategory implements RequestQueryBuilderPipe
{
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $allowed = collect(CompanyCategory::getValues())->map(fn (string $v) => "'$v'")->implode(', ');

        $request->validate(
            rules: [
                'filter.category.*' => [Rule::in(CompanyCategory::getValues())],
            ],
            messages: [
                'filter.category.*.in' => "The selected :attribute is invalid, allowed: $allowed."
            ]
        );

        if (false === $request->has('filter.category')) {
            return;
        }

        $filter = Arr::wrap($request->input('filter.category'));

        $builder->where(function (Builder $builder) use ($filter) {
            $builder->where($builder->qualifyColumn('category'), $filter);
        });
    }
}