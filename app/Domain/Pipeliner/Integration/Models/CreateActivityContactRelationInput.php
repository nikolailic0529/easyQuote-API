<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityContactRelationInput extends BaseInput
{
    public function __construct(public readonly string $contactId)
    {
    }
}
