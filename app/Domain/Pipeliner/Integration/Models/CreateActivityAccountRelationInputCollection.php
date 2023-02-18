<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityAccountRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateActivityAccountRelationInput
    {
        return parent::current();
    }
}
