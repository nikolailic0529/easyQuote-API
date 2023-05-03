<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityClientRelationInput extends BaseInput
{
    public function __construct(public readonly string $clientId)
    {
    }
}
