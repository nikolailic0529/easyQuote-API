<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateCurrencyInput extends BaseInput
{
    public function __construct(
        public readonly string $code,
        public readonly CreateCurrencyExchangeRateInputCollection $currencyExchangeRates,
        public readonly string|InputValueEnum $symbol = InputValueEnum::Miss
    ) {
    }
}