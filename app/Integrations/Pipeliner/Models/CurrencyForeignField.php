<?php

namespace App\Integrations\Pipeliner\Models;

use JetBrains\PhpStorm\Pure;

class CurrencyForeignField
{
    public function __construct(public readonly float $baseValue,
                                public readonly string $currencyId,
                                public readonly float $valueForeign)
    {
    }

    #[Pure]
    public static function fromArray(array $array): static
    {
        return new static(
            baseValue: $array['baseValue'],
            currencyId: $array['currencyId'],
            valueForeign: $array['valueForeign']
        );
    }
}