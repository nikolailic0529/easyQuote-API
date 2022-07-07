<?php

namespace App\Services\Pipeliner\Strategies;

use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;

class StrategyNameResolver
{
    public function __construct(public readonly SyncStrategy $strategy)
    {
    }

    public static function from(SyncStrategy $strategy): static
    {
        return new static($strategy);
    }

    public function __toString(): string
    {
        return array_search($this->strategy::class, config('pipeliner.sync.strategies'), true) ?: class_basename($this->strategy);
    }
}