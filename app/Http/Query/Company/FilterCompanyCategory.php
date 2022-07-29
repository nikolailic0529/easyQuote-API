<?php

namespace App\Http\Query\Company;

use App\Enum\CompanyCategoryEnum;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class FilterCompanyCategory implements RequestQueryBuilderPipe
{
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $allowed = collect(CompanyCategoryEnum::cases())
            ->map(fn(CompanyCategoryEnum $v) => "`$v->value`")
            ->implode(', ');

        $request->validate(
            rules: [
                'filter.category.*' => [new Enum(CompanyCategoryEnum::class)],
            ],
            messages: [
                'filter.category.*.in' => "The selected :attribute is invalid, allowed: $allowed.",
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