<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateOrUpdateContactInputCollection extends BaseInputCollection
{
    public function current(): CreateContactInput|UpdateContactInput
    {
        return parent::current();
    }
}
