<?php

namespace App\Foundation\Http\Client\ConnectionLimiter;

use Illuminate\Cache\NoLock;
use Illuminate\Contracts\Cache\Lock;

class InMemoryStore implements Store
{
    protected int $connections = 0;

    public function get(): int
    {
        return $this->connections;
    }

    public function increment(): int
    {
        return ++$this->connections;
    }

    public function decrement(): int
    {
        return --$this->connections;
    }

    public function getMutex(int $seconds = 0): Lock
    {
        return new NoLock(name: static::class, seconds: $seconds);
    }
}
