<?php

namespace App\Services\ExchangeRate;

use App\DTO\ExchangeRate\ExchangeRateCollection;
use App\DTO\ExchangeRate\ExchangeRateData;
use Carbon\Carbon;
use DateTimeInterface;

class NullExchangeRatesProvider extends ExchangeRateService
{
    public function getRatesData(DateTimeInterface $dateTime): ExchangeRateCollection
    {
        return new ExchangeRateCollection();
    }

    public function getRateDataOfCurrency(string $currencyCode, DateTimeInterface $dateTime): ?ExchangeRateData
    {
        return null;
    }

    public function parseRatesDateFromFile(string $filepath): Carbon
    {
        return Carbon::now();
    }

    public function buildRequestUrl(DateTimeInterface $dateTime): string
    {
        return '';
    }

    public function baseCurrency(): string
    {
        return '';
    }
}