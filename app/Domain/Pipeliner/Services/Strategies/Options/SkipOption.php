<?php

namespace App\Domain\Pipeliner\Services\Strategies\Options;

use App\Domain\Pipeliner\Services\Strategies\Contracts\SyncStrategy;

class SkipOption
{
    public readonly string $strategy;
    public readonly array $entities;
    public readonly \Closure $compareEntitiesCallback;

    protected function __construct(
        string $strategy = '',
        array $entities = [],
        ?\Closure $compareEntitiesCallback = null
    ) {
        $this->strategy = $strategy;
        $this->entities = $entities;
        $this->compareEntitiesCallback = $compareEntitiesCallback ?? static fn (mixed $a, mixed $b): bool => $a === $b;
    }

    public static function new(): static
    {
        return new static();
    }

    public function implies(string|object $strategy, mixed $entity = null): bool
    {
        if (!is_a($strategy, $this->strategy, true)) {
            return false;
        }

        if ($this->entities === [] || null === $entity) {
            return true;
        }

        return collect($this->entities)
            ->containsStrict(function (mixed $optionEntity) use ($entity): bool {
                return ($this->compareEntitiesCallback)($optionEntity, $entity);
            });
    }

    public function forStrategy(string $strategy): static
    {
        if (!is_a($strategy, SyncStrategy::class, true)) {
            throw new \InvalidArgumentException(sprintf('Strategy class must be an instance of [%s]', SyncStrategy::class));
        }

        return new static(strategy: $strategy, entities: $this->entities);
    }

    public function compareEntitiesUsing(callable $callback): static
    {
        return new static(
            strategy: $this->strategy,
            entities: $this->entities,
            compareEntitiesCallback: $callback
        );
    }

    public function forEntities(mixed $entity, mixed ...$entities): static
    {
        return new static(
            strategy: $this->strategy,
            entities: [$entity, ...array_values($entities)],
            compareEntitiesCallback: $this->compareEntitiesCallback
        );
    }
}
