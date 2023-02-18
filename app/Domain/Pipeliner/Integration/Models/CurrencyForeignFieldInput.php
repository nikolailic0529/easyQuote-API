<?php

namespace App\Domain\Pipeliner\Integration\Models;

final class CurrencyForeignFieldInput extends BaseInput
{
    public function __construct(
        public readonly float $baseValue,
        public readonly string $currencyId,
        public readonly float $valueForeign
    ) {
    }
}
