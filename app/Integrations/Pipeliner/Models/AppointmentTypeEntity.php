<?php

namespace App\Integrations\Pipeliner\Models;

use DateTimeImmutable;

class AppointmentTypeEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $isReadonly,
        public readonly bool $canChangeReadonly,
        public DateTimeImmutable $created,
        public DateTimeImmutable $modified,
        public readonly int $revision
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            name: $array['name'],
            isReadonly: $array['isReadonly'],
            canChangeReadonly: $array['canChangeReadonly'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}