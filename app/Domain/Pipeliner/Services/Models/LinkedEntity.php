<?php

namespace App\Domain\Pipeliner\Services\Models;

class LinkedEntity
{
    public function __construct(public readonly string $entityName,
                                public readonly string $id,
                                public readonly string $plReference,
                                public readonly bool|null $isValid)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            entityName: $array['entity_name'],
            id: $array['id'],
            plReference: $array['pl_reference'],
            isValid: $array['is_valid']
        );
    }
}
