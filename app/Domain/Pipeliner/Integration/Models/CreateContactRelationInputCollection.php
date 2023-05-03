<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateContactRelationInputCollection extends BaseInputCollection
{
    protected readonly \ArrayIterator $iterator;

    public function __construct(CreateContactRelationInput ...$collection)
    {
        parent::__construct(...$collection);
    }

    public function current(): CreateContactRelationInput
    {
        return $this->iterator->current();
    }
}
