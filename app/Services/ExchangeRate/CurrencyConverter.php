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

    public function convertCurrencies(string $fromCode,
                                      string $toCode,
                                      float $amount,
                                      ?\DateTimeInterface $dateTime = null): float
    {
        $baseRateOfFromCurrencyCode = $this->exchangeRateService->getBaseRateByCurrencyCode($fromCode, $dateTime);
        $baseRateOfToCurrencyCode = $this->exchangeRateService->getBaseRateByCurrencyCode($toCode, $dateTime);

        return ($amount * $baseRateOfFromCurrencyCode) / $baseRateOfToCurrencyCode;
    }

    public function getBaseCurrency(): string
    {
        return $this->exchangeRateService->baseCurrency();
    }
}
