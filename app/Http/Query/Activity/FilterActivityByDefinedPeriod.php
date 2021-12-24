<?php

namespace App\Http\Query\Activity;

use App\Helpers\CarbonPeriodBuilder;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;

class FilterActivityByDefinedPeriod implements RequestQueryBuilderPipe
{
    /**
     * @inheritDoc
     */
    public function __invoke(BuildQueryParameters $parameters): void
    {
        $request = $parameters->getRequest();
        $builder = $parameters->getBuilder();

        $periodValue = $request->input('period');

        if (false === $this->validateRequestValue($periodValue)) {
            return;
        }

        $carbonPeriod = CarbonPeriodBuilder::buildFromPeriodName($periodValue);

        $builder->where(function (Builder $builder) use ($carbonPeriod) {
            $builder->whereBetween($builder->getModel()->getQualifiedCreatedAtColumn(), [
                $carbonPeriod->getStartDate(),
                $carbonPeriod->getEndDate(),
            ]);
        });
    }

    protected function validateRequestValue(mixed $value): bool
    {
        return is_string($value) &&
            CarbonPeriodBuilder::isValidPeriodName($value);
    }
}