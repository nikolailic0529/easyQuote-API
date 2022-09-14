<?php

namespace App\Integrations\Pipeliner\Models;

class ActivityClientRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $clientId
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(id: $array['id'], clientId: $array['clientId']);
    }
}