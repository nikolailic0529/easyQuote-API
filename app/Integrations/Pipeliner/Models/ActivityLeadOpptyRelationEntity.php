<?php

namespace App\Integrations\Pipeliner\Models;

class ActivityLeadOpptyRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $leadOpptyId
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(id: $array['id'], leadOpptyId: $array['leadOpptyId']);
    }
}