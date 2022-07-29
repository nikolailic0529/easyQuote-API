<?php

namespace App\Integrations\Pipeliner\Models;

class CreateOrUpdateContactAccountRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateOrUpdateContactAccountRelationInput
    {
        return parent::current();
    }
}