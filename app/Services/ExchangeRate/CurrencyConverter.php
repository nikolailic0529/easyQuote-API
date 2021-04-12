<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Services\ManagesExchangeRates;

class CurrencyConverter
{
    protected ManagesExchangeRates $exchangeRateService;

    public function __construct(ManagesExchangeRates $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function convertCurrencies(string $fromCurrencyCode, string $toCurrencyCode, float $amount): float
    {
        $baseRateOfFromCurrencyCode = $this->exchangeRateService->getBaseRateByCurrencyCode($fromCurrencyCode);
        $baseRateOfToCurrencyCode = $this->exchangeRateService->getBaseRateByCurrencyCode($toCurrencyCode);

        return ($amount * $baseRateOfFromCurrencyCode) / $baseRateOfToCurrencyCode;
    }

    public function getBaseCurrency(): string
    {
        return $this->exchangeRateService->baseCurrency();
    }
}
