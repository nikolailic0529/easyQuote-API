<?php

namespace App\Services\Pipeliner;

use Illuminate\Contracts\Cache\Repository as Cache;

class PipelinerAggregateSyncEventService
{
    public function __construct(
        protected readonly Cache $cache,
    ) {
    }

    public function rememberPendingCounts(string $aggregateId, iterable $pending): void
    {
        foreach ($pending as $strategyClass => $count) {
            $this->cache->add(
                key: $this->getStrategyPendingCountCacheKey($aggregateId, $strategyClass),
                value: $count
            );

            $this->cache->add(
                key: $this->getMutStrategyPendingCountCacheKey($aggregateId, $strategyClass),
                value: $count
            );
        }
    }

    public function decrementPendingCount(string $aggregateId, string $strategy): void
    {
        $this->cache->decrement(
            $this->getMutStrategyPendingCountCacheKey($aggregateId, $strategy)
        );
    }

    public function getPendingCount(string $aggregateId, string $strategy): int
    {
        return (int)$this->cache->get(
            key: $this->getStrategyPendingCountCacheKey($aggregateId, $strategy),
            default: 0
        );
    }

    public function getMutatedPendingCount(string $aggregateId, string $strategy): int
    {
        return (int)$this->cache->get(
            key: $this->getMutStrategyPendingCountCacheKey($aggregateId, $strategy),
            default: 0
        );
    }

    private function getStrategyPendingCountCacheKey(string $aggregateId, string $strategyClass): string
    {
        return static::class.':pending-count'.$strategyClass.$aggregateId;
    }

    private function getMutStrategyPendingCountCacheKey(string $aggregateId, string $strategyClass): string
    {
        return static::class.':pending-count-mut'.$strategyClass.$aggregateId;
    }
}