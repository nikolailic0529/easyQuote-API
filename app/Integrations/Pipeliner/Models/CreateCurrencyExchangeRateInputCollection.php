<?php

namespace App\Integrations\Pipeliner\Models;

class CreateCurrencyExchangeRateInputCollection extends BaseInputCollection
{
    public function current(): CreateCurrencyExchangeRateInput
    {
        return parent::current();
    }
}