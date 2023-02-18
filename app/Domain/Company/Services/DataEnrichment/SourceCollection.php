<?php

namespace App\Domain\Company\Services\DataEnrichment;

use App\Domain\Company\Services\DataEnrichment\Sources\Source;

final class SourceCollection implements \Iterator
{
    private readonly \ArrayIterator $iterator;

    public function __construct(
        Source ...$sources,
    ) {
        $this->iterator = new \ArrayIterator($sources);
    }

    public function current(): Source
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): int
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
}
