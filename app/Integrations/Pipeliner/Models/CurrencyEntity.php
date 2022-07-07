<?php

namespace App\Integrations\Pipeliner\Models;

class CurrencyEntity
{
    public function __construct(public readonly string $id,
                                public readonly string $code)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            code: $array['code']
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        if (is_null($array)) {
            return null;
        }

        return self::fromArray($array);
    }
}