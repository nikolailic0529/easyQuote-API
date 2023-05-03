<?php

namespace App\Domain\Pipeliner\Integration\Models;

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
