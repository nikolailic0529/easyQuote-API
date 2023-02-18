<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Pipeliner\Services\Strategies\Contracts\SyncStrategy;

class SyncStrategyCollection implements \Iterator, \ArrayAccess
{
    protected readonly \ArrayIterator $iterator;

    public function __construct(SyncStrategy ...$strategies)
    {
        $array = [];

        foreach ($strategies as $strategy) {
            $array[$strategy::class] = $strategy;
        }

        $this->iterator = new \ArrayIterator($array);
    }

    public function current(): SyncStrategy
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->iterator->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): SyncStrategy
    {
        return $this->iterator->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->iterator->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->iterator->offsetUnset($offset);
    }
}
