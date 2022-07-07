<?php

namespace App\Integrations\Pipeliner\Models;

class CreateActivityAccountRelationInput extends BaseInput
{
    public function __construct(public readonly string $accountId)
    {
    }
}