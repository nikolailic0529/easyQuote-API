<?php

namespace App\Integrations\Pipeliner\Models;

class CurrencyExchangeRatesListEntity
{
    public function __construct(public readonly string             $id,
                                public readonly \DateTimeImmutable $validFrom,
                                public readonly \DateTimeImmutable $created,
                                public readonly \DateTimeImmutable $modified,
                                public readonly int                $revision)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            validFrom: Entity::parseDateTime($array['validFrom']),
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision']
        );
    }
}