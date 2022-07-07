<?php

namespace App\Integrations\Pipeliner\Models;

class CreateActivityLeadOpptyRelationInput extends BaseInput
{
    public function __construct(public readonly string $leadOpptyId)
    {
    }
}