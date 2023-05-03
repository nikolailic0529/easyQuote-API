<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\Pipeline;

use App\Foundation\Database\Eloquent\QueryFilter\Enum\OperatorEnum;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FilterRelationPipe implements RequestQueryBuilderPipe
{
    protected \Closure $valueProcessor;

    public function __construct(
        protected string $field,
        protected string $relation,
        protected ?OperatorEnum $operator = null,
        ?callable $valueProcessor = null,
    ) {
        $this->valueProcessor = ($valueProcessor ?? static fn ($value) => $value)(...);
    }

    public function __invoke(BuildQueryParameters $parameters): void
    {
        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

        $filters = $this->getFilters($request);

        $value = $filters->get($this->field);

        if ($value === null) {
            return;
        }

        $value = call_user_func($this->valueProcessor, $value);

        [$relation, $column] = explode('.', $this->relation, 2) + [$this->relation, 'id'];

        $builder->where(function (Builder $builder) use ($column, $relation, $value): void {
            $builder->whereHas($relation, function (Builder $builder) use ($column, $value): void {
                if (null !== $this->operator) {
                    match ($this->operator) {
                        OperatorEnum::In => $builder->whereIn($column, Arr::wrap($value)),
                        OperatorEnum::NotIn => $builder->whereNotIn($column, Arr::wrap($value)),
                        default => (function () use ($column, $value, $builder): void {
                            foreach (Arr::wrap($value) as $v) {
                                $builder->where($column, $this->operator->value, $v);
                            }
                        })(),
                    };
                } else {
                    $builder->when(
                        is_array($value),
                        fn (Builder $builder) => $builder->whereIn($column, $value),
                        fn (Builder $builder) => $builder->where($column, $value),
                    );
                }
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
