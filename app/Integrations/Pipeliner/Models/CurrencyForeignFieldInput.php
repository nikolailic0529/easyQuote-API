<?php

namespace App\Integrations\Pipeliner\Models;

final class CurrencyForeignFieldInput extends BaseInput
{
    public function __construct(
        public readonly float $baseValue,
        public readonly string $currencyId,
        public readonly float $valueForeign
    ) {
    }
}