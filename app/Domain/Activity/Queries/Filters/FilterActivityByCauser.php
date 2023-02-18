<?php

namespace App\Domain\Activity\Queries\Filters;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;

class FilterActivityByCauser implements RequestQueryBuilderPipe
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $requestValue = $request->input('causer_id');

        if (false === $this->validateRequestValue($requestValue)) {
            return;
        }

        $builder->where(function (Builder $builder) use ($requestValue) {
            $builder->where($builder->qualifyColumn('causer_id'), $requestValue);
        });
    }

    protected function validateRequestValue(mixed $value): bool
    {
        return is_string($value) &&
            false === empty($value);
    }
}
