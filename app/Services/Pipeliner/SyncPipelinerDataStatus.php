<?php

namespace App\Services\Pipeliner;

use Illuminate\Contracts\Cache\Repository as Cache;

class SyncPipelinerDataStatus implements \JsonSerializable
{
    public function __construct(
        protected readonly Cache $cache,
        protected readonly string $prefix = 'sync_pipeliner_data_status',
    ) {
    }

    public function enable(): void
    {
        $this->cache->set($this->getStatusKey(), true);
    }

    public function disable(): void
    {
        $this->cache->forget($this->getStatusKey());
    }

    public function running(): bool
    {
        return (bool) $this->cache->get($this->getStatusKey());
    }

    public function setTotal(int $value): void
    {
        $this->cache->set($this->getTotalKey(), $value);
    }

    public function total(): int
    {
        return $this->cache->get($this->getTotalKey(), 0);
    }

    public function setProcessed(int $value): void
    {
        $this->cache->set($this->getProcessedKey(), $value);
    }

    public function processed(): int
    {
        return min($this->cache->get($this->getProcessedKey(), 0), $this->total());
    }

    public function incrementProcessed(): void
    {
        $this->cache->increment($this->getProcessedKey());
    }

    private function getStatusKey(): string
    {
        return $this->prefix.':status';
    }

    private function getProcessedKey(): string
    {
        return $this->prefix.':processed';
    }

    private function getTotalKey(): string
    {
        return $this->prefix.':total';
    }

    public function clear(): void
    {
        $this->cache->deleteMultiple([
            $this->getTotalKey(),
            $this->getProcessedKey(),
            $this->getStatusKey(),
        ]);
    }

    public function jsonSerialize(): array
    {
        return [
            'running' => $this->running(),
            'progress' =>  $this->progress(),
            'total_entities' => $this->total(),
            'pending_entities' => $this->total() - $this->processed(),
        ];
    }

    public function progress(): int
    {
        return $this->total() > 0 ? round(($this->processed() / $this->total()) * 100) : 0;
    }
}