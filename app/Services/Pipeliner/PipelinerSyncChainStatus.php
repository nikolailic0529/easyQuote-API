<?php

namespace App\Services\Pipeliner;

use Illuminate\Contracts\Cache\Repository;

final class PipelinerSyncChainStatus
{
    public function __construct(
        public readonly string $id,
        protected readonly Repository $cache,
    ) {
    }

    public function terminate(): void
    {
        $this->cache->add($this->getTerminatedKey(), true);
    }

    public function isTerminated(): bool
    {
        return (bool) $this->cache->get($this->getTerminatedKey());
    }

    public function isNotTerminated(): bool
    {
        return !$this->isTerminated();
    }

    protected function getTerminatedKey(): string
    {
        return $this->getCacheKey().':terminated';
    }

    protected function getCacheKey(): string
    {
        return 'pipeliner-sync-chain:'.PipelinerSyncChainStatus::class.$this->id;
    }
}