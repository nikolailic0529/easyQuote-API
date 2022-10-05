<?php

namespace App\Integrations\Pipeliner\Models;

class CreateOrUpdateContactInputCollection extends BaseInputCollection
{
    public function current(): CreateContactInput|UpdateContactInput
    {
        return parent::current();
    }
}