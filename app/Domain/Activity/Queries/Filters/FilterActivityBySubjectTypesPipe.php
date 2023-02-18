<?php

namespace App\Domain\Activity\Queries\Filters;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

class FilterActivityBySubjectTypesPipe implements RequestQueryBuilderPipe
{
    public function __construct(protected array $allowedSubjectTypes)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $requestValue = Arr::wrap($request->input('subject_types'));

        if (false === $this->validateRequestValue($requestValue)) {
            return;
        }

        $subjectTypes = Arr::flatten(Arr::only($this->allowedSubjectTypes, $requestValue));

        $morphMap = array_flip(Relation::$morphMap);

        $subjectTypes = array_map(
            fn (string $className) => $morphMap[$className] ?? $className,
            $subjectTypes
        );

        $builder->where(function (Builder $builder) use ($subjectTypes) {
            $builder->whereIn($builder->qualifyColumn('subject_type'), $subjectTypes);
        });
    }

    protected function validateRequestValue(mixed $value): bool
    {
        return is_array($value) &&
            false === empty($value);
    }
}
