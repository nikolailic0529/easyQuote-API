<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Pipeliner\Services\Strategies\Contracts\SyncStrategy;

class StrategyNameResolver
{
    public function __construct(public readonly string $strategy)
    {
    }

    public static function from(SyncStrategy|string $strategy): static
    {
        if (is_object($strategy)) {
            $strategy = $strategy::class;
        }

        return new static($strategy);
    }

    public function __toString(): string
    {
        return array_search($this->strategy, config('pipeliner.sync.strategies'), true) ?: class_basename($this->strategy);
    }
}
