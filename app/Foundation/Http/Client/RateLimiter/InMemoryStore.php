<?php

namespace App\Foundation\Http\Client\RateLimiter;

class InMemoryStore implements Store
{
    /** @var int[] */
    protected array $timestamps = [];

    public function get(): array
    {
        return $this->timestamps;
    }

    public function push(int $timestamp, int $limit): void
    {
        $this->timestamps[] = $timestamp;
    }
}
