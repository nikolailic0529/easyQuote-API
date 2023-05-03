<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;

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
