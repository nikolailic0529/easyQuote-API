<?php

namespace App\Domain\Pipeliner\Integration\Models;

class MasterRight
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            name: $array['name']
        );
    }
}
