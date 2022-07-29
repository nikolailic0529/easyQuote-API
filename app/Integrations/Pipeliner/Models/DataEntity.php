<?php

namespace App\Integrations\Pipeliner\Models;

use JetBrains\PhpStorm\Pure;

class DataEntity
{
    public function __construct(public readonly string $id,
                                public readonly string $optionName,
                                public readonly float  $calcValue,
                                public readonly ?array $allowedBy)
    {
    }

    #[Pure]
    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            optionName: $array['optionName'],
            calcValue: $array['calcValue'],
            allowedBy: $array['allowedBy'] ?? null
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        return isset($array) ? static::fromArray($array) : null;
    }
}