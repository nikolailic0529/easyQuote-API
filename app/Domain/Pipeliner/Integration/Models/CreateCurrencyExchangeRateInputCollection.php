<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateCurrencyExchangeRateInputCollection extends BaseInputCollection
{
    public function current(): CreateCurrencyExchangeRateInput
    {
        return parent::current();
    }
}
