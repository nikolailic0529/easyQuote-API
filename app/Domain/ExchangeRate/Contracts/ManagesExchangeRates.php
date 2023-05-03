<?php

namespace App\Domain\ExchangeRate\Contracts;

use App\Domain\Currency\Models\Currency;
use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateCollection;
use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateData;
use Carbon\Carbon;

interface ManagesExchangeRates
{
    final const FALLBACK_LATEST = 1 << 0;

    /**
     * Get fresh rates data from an dedicated resource.
     */
    public function getRatesData(\DateTimeInterface $dateTime): ExchangeRateCollection;

    /**
     * Get fresh exchange rate data of the specified currency.
     */
    public function getRateDataOfCurrency(string $currencyCode, \DateTimeInterface $dateTime): ?ExchangeRateData;

    /**
     * Update Exchange Rates in storage.
     */
    public function updateRates(): bool;

    /**
     * Update Exchange Rates using data from given file.
     */
    public function updateRatesFromFile(string $filepath, Carbon $date): bool;

    /**
     * Parse rates date from the specified file.
     */
    public function parseRatesDateFromFile(string $filepath): Carbon;

    /**
     * Calculate target exchange rate based on source & target currencies.
     */
    public function getTargetRate(Currency $source, Currency $target, ?int $precision = null): float;

    /**
     * Calculate base exchange rate based on source currency and base_currency setting.
     */
    public function getBaseRate(Currency $source, ?\DateTimeInterface $dateTime = null): float;

    public function getBaseRateByCurrencyCode(string $currencyCode, ?\DateTimeInterface $dateTime = null, int $mode = 0): float;

    public function getRateByCurrencyCode(string $currencyCode, ?\DateTimeInterface $dateTime = null, int $mode = 0): float;

    /**
     * Format the request url with the given period.
     */
    public function buildRequestUrl(\DateTimeInterface $dateTime): string;

    /**
     * Service Base Currency Code.
     */
    public function baseCurrency(): string;
}
