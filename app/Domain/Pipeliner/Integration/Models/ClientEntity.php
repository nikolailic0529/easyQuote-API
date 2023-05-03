<?php

namespace App\Domain\Pipeliner\Integration\Models;

class ClientEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $formattedName,
        public readonly string $firstName,
        public readonly string $lastName
    ) {
    }

    public static function tryFromArray(?array $array): static|null
    {
        return is_array($array) ? static::fromArray($array) : null;
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            email: $array['email'],
            formattedName: $array['formattedName'],
            firstName: $array['firstName'],
            lastName: $array['lastName']
        );
    }
}
