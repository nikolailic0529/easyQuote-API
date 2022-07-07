<?php

namespace App\Integrations\Pipeliner\Models;

class ContactRelationEntity
{
    public function __construct(public readonly string $id,
                                public readonly bool $isPrimary,
                                public readonly ContactEntity $contact)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            isPrimary: $array['isPrimary'],
            contact: ContactEntity::fromArray($array['contact'])
        );
    }
}