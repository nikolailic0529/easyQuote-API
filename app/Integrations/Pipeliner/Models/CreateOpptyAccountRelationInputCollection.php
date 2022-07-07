<?php

namespace App\Integrations\Pipeliner\Models;

final class CreateOpptyAccountRelationInputCollection extends BaseInputCollection
{
    public function __construct(CreateOpptyAccountRelationInput ...$collection)
    {
        parent::__construct(...$collection);
    }

    public function current(): CreateOpptyAccountRelationInput
    {
        return parent::current();
    }
}