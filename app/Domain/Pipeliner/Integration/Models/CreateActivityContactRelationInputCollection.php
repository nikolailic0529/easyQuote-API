<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityContactRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateActivityContactRelationInput
    {
        return parent::current();
    }
}
