<?php

namespace App\Domain\Shared\Ownership;

use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use ArrayIterator;
use Countable;
use Iterator;

class ChangeOwnershipStrategyCollection implements Iterator, Countable
{
    protected readonly ArrayIterator $delegate;

    public function __construct(
        ChangeOwnershipStrategy ...$strategies
    ) {
        $this->delegate = new ArrayIterator($strategies);
    }

    public function current(): ChangeOwnershipStrategy
    {
        return $this->delegate->current();
    }

    public function next(): void
    {
        $this->delegate->next();
    }

    public function key(): int
    {
        return $this->delegate->key();
    }

    public function valid(): bool
    {
        return $this->delegate->valid();
    }

    public function rewind(): void
    {
        $this->delegate->rewind();
    }

    public function count(): int
    {
        return $this->delegate->count();
    }
}
