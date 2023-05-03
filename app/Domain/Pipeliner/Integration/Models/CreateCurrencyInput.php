<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class CreateCurrencyInput extends BaseInput
{
    public function __construct(
        public readonly string $code,
        public readonly CreateCurrencyExchangeRateInputCollection $currencyExchangeRates,
        public readonly string|InputValueEnum $symbol = InputValueEnum::Miss
    ) {
    }
}
