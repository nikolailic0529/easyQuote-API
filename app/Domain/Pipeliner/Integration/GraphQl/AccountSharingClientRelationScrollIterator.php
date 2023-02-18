<?php

namespace App\Domain\Pipeliner\Integration\GraphQl;

use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationEntity;

class AccountSharingClientRelationScrollIterator implements \Iterator
{
    public function __construct(
        protected readonly \Generator $generator
    ) {
    }

    public function current(): AccountSharingClientRelationEntity
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
    }
}
