<?php

namespace App\Integrations\Pipeliner\Models;

class CreateActivityClientRelationInput extends BaseInput
{
    public function __construct(public readonly string $clientId)
    {
    }
}