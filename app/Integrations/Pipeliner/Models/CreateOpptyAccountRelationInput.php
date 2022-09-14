<?php

namespace App\Integrations\Pipeliner\Models;

final class CreateOpptyAccountRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $accountId,
        public readonly bool $isPrimary
    ) {
    }
}