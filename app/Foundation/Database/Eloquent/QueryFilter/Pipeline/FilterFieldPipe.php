<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\Pipeline;

use App\Foundation\Database\Eloquent\QueryFilter\Enum\OperatorEnum;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FilterFieldPipe implements RequestQueryBuilderPipe
{
    protected string $column;
    protected \Closure $valueProcessor;

    public function __construct(
        protected string $field,
        ?string $column = null,
        protected ?OperatorEnum $operator = null,
        ?callable $valueProcessor = null,
    ) {
        $this->column = $column ?? $field;
        $this->valueProcessor = ($valueProcessor ?? static fn ($value) => $value)(...);
    }

    public function __invoke(BuildQueryParameters $parameters): void
    {
        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

        $filters = $this->getFilters($request);

        $value = Arr::get($filters, $this->field);

        if ($value === null) {
            return;
        }

        $value = call_user_func($this->valueProcessor, $value);

        $builder->where(function (Builder $builder) use ($value): void {
            if (null !== $this->operator) {
                match ($this->operator) {
                    OperatorEnum::In => $builder->whereIn($this->column, Arr::wrap($value)),
                    OperatorEnum::NotIn => $builder->whereNotIn($this->column, Arr::wrap($value)),
                    default => (function () use ($value, $builder): void {
                        foreach (Arr::wrap($value) as $v) {
                            $builder->where($this->column, $this->operator->value, $v);
                        }
                    })(),
                };
            } else {
                if (is_array($value)) {
                    $builder->whereIn($this->column, $value);
                } else {
                    $builder->where($this->column, $value);
                }
            }
        });
    }

    public function processValueWith(callable $callback): static
    {
        return tap($this, fn () => $this->valueProcessor = $callback(...));
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
