<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Integrations\Pipeliner\Models\OpportunityEntity;
use JetBrains\PhpStorm\Internal\TentativeType;
use Traversable;

class OpportunityEntityScrollIterator implements \Iterator
{
    public function __construct(protected readonly \Generator $generator)
    {
    }

    public function current(): OpportunityEntity
    {
        return $this->generator->current();
    }

    public function next(): void
    {
        $this->generator->next();
    }

    public function key(): mixed
    {
        return $this->generator->key();
    }

    public function valid(): bool
    {
        return $this->generator->valid();
    }

    public function rewind(): void
    {
        //
    }
}