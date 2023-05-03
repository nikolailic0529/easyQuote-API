<?php

namespace App\Domain\Pipeliner\Integration\Models;

final class CreateOpptyAccountRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $accountId,
        public readonly bool $isPrimary
    ) {
    }
}
