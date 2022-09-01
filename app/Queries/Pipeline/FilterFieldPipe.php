<?php

namespace App\Queries\Pipeline;

use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FilterFieldPipe implements RequestQueryBuilderPipe
{
    protected string $field;
    protected ?string $column;

    public function __construct(
        string $field,
        ?string $column = null,
    ) {
        $this->field = $field;
        $this->column = $column ?? $field;
    }

    public function __invoke(BuildQueryParameters $parameters): void
    {
        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

        $filters = $this->getFilters($request);

        $value = $filters->get($this->field);

        if ($value === null) {
            return;
        }

        $builder->where(function (Builder $builder) use ($value): void {
            if (is_array($value)) {
                $builder->whereIn($this->column, $value);
            } else {
                $builder->where($this->column, $value);
            }
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