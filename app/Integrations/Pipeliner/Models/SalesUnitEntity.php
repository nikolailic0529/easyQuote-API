<?php

namespace App\Integrations\Pipeliner\Models;

use JetBrains\PhpStorm\Pure;

class SalesUnitEntity
{
    public function __construct(public readonly string $id,
                                public readonly string $name)
    {
    }

    #[Pure]
    public static function fromArray(array $array): SalesUnitEntity
    {
        return new static(id: $array['id'], name: $array['name']);
    }
}