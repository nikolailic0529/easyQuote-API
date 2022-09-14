<?php

namespace App\Integrations\Pipeliner\Models;

class ActivityAccountRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(id: $array['id'], accountId: $array['accountId']);
    }
}