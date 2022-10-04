<?php

namespace App\Services\Pipeliner;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

class SyncPipelinerDataStatus implements \JsonSerializable
{
    protected string $owner;

    public function __construct(
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
        string $owner = null,
    ) {
        $this->owner = $owner ?? Str::random();
    }

    public function setOwner(string $owner): static
    {
        return tap($this, fn () => $this->owner = $owner);
    }

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    public function acquire(): bool
    {
        $acquired = (bool) $this->lock()->get();

        if ($acquired) {
            $this->cache->set($this->getStatusKey(), true);
        }

        return $acquired;
    }

    public function release(): bool
    {
        if ($released = $this->lock()->release()) {
            $this->flush();
        }

        return $released;
    }

    public function forceRelease(): bool
    {
        $this->lock()->forceRelease();
        $this->flush();

        return true;
    }

    protected function flush(): void
    {
        $this->cache->deleteMultiple([
            $this->getStatusKey(),
            $this->getTotalKey(),
            $this->getProcessedKey(),
        ]);
    }

    protected function lock(): Lock
    {
        return $this->lockProvider->lock(static::class, owner: $this->owner);
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

    public function incrementTotal(int $value = 1): void
    {
        $this->cache->increment($this->getTotalKey(), $value);
    }

    public function setProcessed(int $value): void
    {
        $this->cache->set($this->getProcessedKey(), $value);
    }

    public function processed(): int
    {
        return min($this->cache->get($this->getProcessedKey(), 0), $this->total());
    }

    public function pending(): int
    {
        return $this->total() - $this->processed();
    }

    public function incrementProcessed(): void
    {
        $this->cache->increment($this->getProcessedKey());
    }

    private function getStatusKey(): string
    {
        return static::class.':status';
    }

    private function getProcessedKey(): string
    {
        return static::class.':processed';
    }

    private function getTotalKey(): string
    {
        return static::class.':total';
    }

    public function jsonSerialize(): array
    {
        return [
            'running' => $this->running(),
            'progress' => $this->progress(),
            'total_entities' => $this->total(),
            'pending_entities' => $this->total() - $this->processed(),
        ];
    }

    public function progress(): int
    {
        return $this->total() > 0 ? round(($this->processed() / $this->total()) * 100) : 0;
    }
}