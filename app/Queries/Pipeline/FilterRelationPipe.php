<?php

namespace App\Queries\Pipeline;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FilterRelationPipe implements RequestQueryBuilderPipe
{
    public function __construct(
        protected string $field,
        protected string $relation,
    ) {
    }

    public function __invoke(BuildQueryParameters $parameters): void
    {
        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

        $filters = $this->getFilters($request);

        $value = $filters->get($this->field);

        if ($value === null) {
            return;
        }

        [$relation, $column] = explode('.', $this->relation, 2) + [$this->relation, 'id'];

        $builder->where(function (Builder $builder) use ($column, $relation, $value): void {
            $builder->whereHas($relation, function (Builder $builder) use ($column, $value): void {
                $builder->when(
                    is_array($value),
                    fn(Builder $builder) => $builder->whereIn($column, $value),
                    fn(Builder $builder) => $builder->where($column, $value),
                );
            });
        });
    }

    protected function normalizeFilterValue(mixed $value): mixed
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(function (mixed $value): mixed {
                    return $this->normalizeFilterValue($value);
                })
                ->all();
        }

        if ('true' === $value) {
            return true;
        }

        if ('false' === $value) {
            return false;
        }

        return $value;
    }

    protected function getFilters(Request $request): Collection
    {
        return $request->collect('filter')->map(function (mixed $value): mixed {
            return $this->normalizeFilterValue($value);
        });
    }
}