<?php

namespace App\Domain\Pipeliner\Services;

use Illuminate\Contracts\Cache\Repository as Cache;

class PipelinerAggregateSyncEventService
{
    public function __construct(
        protected readonly Cache $cache,
    ) {
    }

    public function incrementUnique(
        string $reference,
        string $aggregateId,
        string $entityType,
    ): void {
        $key = $this->getStrategyCountCacheKey($aggregateId, $entityType);

        if ($this->cache->add($key.$reference, true)) {
            $this->cache->increment(
                $this->getStrategyCountCacheKey($aggregateId, $entityType)
            );
        }
    }

    public function count(string $aggregateId, string $entityType): int
    {
        return (int) $this->cache->get(
            key: $this->getStrategyCountCacheKey($aggregateId, $entityType),
            default: 0
        );
    }

    private function getStrategyCountCacheKey(string $aggregateId, string $entityType): string
    {
        return static::class.':pending-count'.$entityType.$aggregateId;
    }
}
