<?php

namespace App\Integrations\Pipeliner\Models;

class PipelineEntity
{
    public function __construct(public readonly string $id,
                                public readonly string $name)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(id: $array['id'], name: $array['name']);
    }

    public static function tryFromArray(?array $array): ?static
    {
        if (is_null($array)) {
            return null;
        }

        return static::fromArray($array);
    }
}