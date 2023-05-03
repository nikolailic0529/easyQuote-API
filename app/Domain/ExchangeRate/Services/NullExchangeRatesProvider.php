<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateCollection;
use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateData;
use Carbon\Carbon;

class NullExchangeRatesProvider extends ExchangeRateService
{
    public function getRatesData(\DateTimeInterface $dateTime): ExchangeRateCollection
    {
        return new ExchangeRateCollection();
    }

    public function getRateDataOfCurrency(string $currencyCode, \DateTimeInterface $dateTime): ?ExchangeRateData
    {
        return null;
    }

    public function parseRatesDateFromFile(string $filepath): Carbon
    {
        return Carbon::now();
    }

    public function buildRequestUrl(\DateTimeInterface $dateTime): string
    {
        return '';
    }

    public function baseCurrency(): string
    {
        return '';
    }
}
