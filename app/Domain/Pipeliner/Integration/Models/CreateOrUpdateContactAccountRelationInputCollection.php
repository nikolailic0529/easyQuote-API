<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateOrUpdateContactAccountRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateOrUpdateContactAccountRelationInput
    {
        return parent::current();
    }
}
