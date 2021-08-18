<?php

namespace App\Http\Query\Activity;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FilterActivityByCustomPeriodPipe implements RequestQueryBuilderPipe
{
    /**
     * @inheritDoc
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $fromDate = $request->input('custom_period.from');
        $tillDate = $request->input('custom_period.till');

        if (false === ($this->validateRequestValue($fromDate) && $this->validateRequestValue($tillDate))) {
            return;
        }

        $builder->where(function (Builder $builder) use ($tillDate, $fromDate) {
            $builder->whereBetween($builder->getModel()->getQualifiedCreatedAtColumn(), [
                Carbon::createFromFormat('Y-m-d', $fromDate),
                Carbon::createFromFormat('Y-m-d', $tillDate),
            ]);
        });

    }

    protected function validateRequestValue(mixed $value): bool
    {
        return is_string($value) &&
            Carbon::createFromFormat('Y-m-d', $value);
    }
}