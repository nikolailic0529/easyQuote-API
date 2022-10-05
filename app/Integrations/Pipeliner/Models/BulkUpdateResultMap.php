<?php

namespace App\Integrations\Pipeliner\Models;

class BulkUpdateResultMap
{
    public function __construct(
        public readonly string $id,
        public readonly int $index,
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            index: $array['index'],
        );
    }
}