<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateActivityAccountRelationInput extends BaseInput
{
    public function __construct(public readonly string $accountId)
    {
    }
}
