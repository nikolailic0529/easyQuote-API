<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityLeadOpptyRelationInput extends BaseInput
{
    public function __construct(public readonly string $leadOpptyId)
    {
    }
}
