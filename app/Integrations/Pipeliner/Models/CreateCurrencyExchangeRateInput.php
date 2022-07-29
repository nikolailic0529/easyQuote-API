<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateCurrencyExchangeRateInput extends BaseInput
{
    public function __construct(public readonly string $currencyExchangeRateListId,
                                public readonly float|InputValueEnum $exchangeRate = InputValueEnum::Miss)
    {
    }
}