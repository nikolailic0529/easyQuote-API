<?php

namespace App\Foundation\Http\Client\ConnectionLimiter;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

class CacheStore implements Store
{
    protected readonly string $prefix;

    public function __construct(
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
        string $prefix = null,
    ) {
        $this->prefix = $prefix ?? Str::random();
    }

    public function get(): int
    {
        return (int) $this->cache->get($this->getCacheKey(), 0);
    }

    public function increment(): int
    {
        return (int) $this->cache->increment($this->getCacheKey());
    }

    public function decrement(): int
    {
        return (int) $this->cache->decrement($this->getCacheKey());
    }

    public function getMutex(int $seconds = 0): Lock
    {
        return $this->lockProvider->lock(name: $this->getMutexName(), seconds: $seconds);
    }

    private function getMutexName(): string
    {
        return static::class.':mutex'.$this->prefix;
    }

    private function getCacheKey(): string
    {
        return static::class.':store'.$this->prefix;
    }
}