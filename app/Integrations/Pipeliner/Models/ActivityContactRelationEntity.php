<?php

namespace App\Integrations\Pipeliner\Models;

class ActivityContactRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $contactId
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(id: $array['id'], contactId: $array['contactId']);
    }
}