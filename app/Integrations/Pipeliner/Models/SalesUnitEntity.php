<?php

namespace App\Integrations\Pipeliner\Models;

class SalesUnitEntity
{
    public function __construct(public readonly string             $id,
                                public readonly string             $name,
                                public readonly \DateTimeImmutable $created,
                                public readonly \DateTimeImmutable $modified,
                                public readonly int                $revision)
    {
    }

    public static function fromArray(array $array): SalesUnitEntity
    {
        return new static(
            id: $array['id'],
            name: $array['name'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        return isset($array) ? static::fromArray($array) : null;
    }
}