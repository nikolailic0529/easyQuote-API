<?php

namespace App\Integrations\Pipeliner\Models;

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