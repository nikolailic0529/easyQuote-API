<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class CreateCurrencyExchangeRateInput extends BaseInput
{
    public function __construct(
        public readonly string $currencyExchangeRateListId,
        public readonly float|InputValueEnum $exchangeRate = InputValueEnum::Miss
    ) {
    }
}
