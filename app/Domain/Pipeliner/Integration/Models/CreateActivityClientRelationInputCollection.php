<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityClientRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateActivityClientRelationInput
    {
        return parent::current();
    }
}
