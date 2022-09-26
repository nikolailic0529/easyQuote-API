<?php

namespace App\Queries\Pipeline;

use App\Queries\Enums\PipeBooleanEnum;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Illuminate\Database\Eloquent\Builder;

class PipeGroup implements RequestQueryBuilderPipe
{
    protected function __construct(
        protected readonly array $pipes,
        protected PipeBooleanEnum $boolean = PipeBooleanEnum::And,
    ) {
    }

    public static function of(RequestQueryBuilderPipe ...$pipes): static
    {
        return new static(
            array_values($pipes),
        );
    }

    public function boolean(PipeBooleanEnum $boolean): static
    {
        return tap($this, fn() => $this->boolean = $boolean);
    }

    public function __invoke(BuildQueryParameters $parameters): void
    {
        $builder = $parameters->getBuilder();

        $builder->where(function (Builder $builder) use ($parameters): void {
            foreach ($this->pipes as $pipe) {
                $builder->where(
                    static function (Builder $builder) use ($parameters, $pipe): void {
                        $pipe(new BuildQueryParameters($builder, $parameters->getRequest()));
                    },
                    boolean: $this->boolean->value
                );
            }
        });
    }
}