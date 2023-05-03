<?php

namespace App\Foundation\Http\Client\RateLimiter;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

class CacheStore implements Store
{
    protected readonly string $prefix;

    public function __construct(
        protected readonly Cache $cache,
        string $prefix = null,
    ) {
        $this->prefix = $prefix ?? Str::random();
    }

    public function get(): array
    {
        return $this->cache->get($this->getCacheKey(), []);
    }

    public function push(int $timestamp, int $limit): void
    {
        $this->cache->put($this->getCacheKey(), [...$this->get(), $timestamp]);
    }

    public function getCacheKey(): string
    {
        return static::class.$this->prefix;
    }
}
