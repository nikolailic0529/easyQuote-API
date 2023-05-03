<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateAccountSharingClientRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateAccountSharingClientRelationInput
    {
        return parent::current();
    }
}