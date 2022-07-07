<?php

namespace App\Integrations\Pipeliner\Models;

class CreateActivityContactRelationInput extends BaseInput
{
    public function __construct(public readonly string $contactId)
    {
    }
}