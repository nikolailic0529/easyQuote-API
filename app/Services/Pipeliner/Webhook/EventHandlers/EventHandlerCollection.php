<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

class EventHandlerCollection implements \Iterator, \ArrayAccess
{
    protected \ArrayIterator $iterator;

    public function __construct(EventHandler ...$handlers)
    {
        $this->iterator = new \ArrayIterator($handlers);
    }

    public function current(): EventHandler
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

    public function offsetGet(mixed $offset): mixed
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