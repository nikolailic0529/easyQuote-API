<?php

namespace App\Contracts\Services;

use App\DTO\ExchangeRate\ExchangeRateCollection;
use App\DTO\ExchangeRate\ExchangeRateData;
use App\Models\Data\Currency;
use Carbon\Carbon;
use DateTimeInterface;

interface ManagesExchangeRates
{
    /**
     * Get fresh rates data from an dedicated resource.
     *
     * @param DateTimeInterface $dateTime
     * @return ExchangeRateCollection
     */
    public function getRatesData(DateTimeInterface $dateTime): ExchangeRateCollection;

    /**
     * Get fresh exchange rate data of the specified currency.
     *
     * @param string $currencyCode
     * @param DateTimeInterface $dateTime
     * @return ExchangeRateData|null
     */
    public function getRateDataOfCurrency(string $currencyCode, DateTimeInterface $dateTime): ?ExchangeRateData;

    /**
     * Update Exchange Rates in storage.
     *
     * @return boolean
     */
    public function updateRates(): bool;

    /**
     * Update Exchange Rates using data from given file.
     *
     * @param string $filepath
     * @param Carbon $date
     * @return boolean
     */
    public function updateRatesFromFile(string $filepath, Carbon $date): bool;

    /**
     * Parse rates date from the specified file.
     *
     * @param string $filepath
     * @return Carbon
     */
    public function parseRatesDateFromFile(string $filepath): Carbon;

    /**
     * Calculate target exchange rate based on source & target currencies.
     *
     * @param \App\Models\Data\Currency $source
     * @param \App\Models\Data\Currency $target
     * @param int|null $precision
     * @return float
     */
    public function getTargetRate(Currency $source, Currency $target, ?int $precision = null): float;

    /**
     * Calculate base exchange rate based on source currency and base_currency setting.
     *
     * @param Currency $source
     * @param DateTimeInterface|null $dateTime
     * @return float
     */
    public function getBaseRate(Currency $source, ?DateTimeInterface $dateTime = null): float;

    /**
     * @param string $currencyCode
     * @param DateTimeInterface|null $dateTime
     * @return float
     */
    public function getBaseRateByCurrencyCode(string $currencyCode, ?DateTimeInterface $dateTime = null): float;

    /**
     * Format the request url with the given period.
     *
     * @param DateTimeInterface $dateTime
     * @return string
     */
    public function buildRequestUrl(DateTimeInterface $dateTime): string;

    /**
     * Service Base Currency Code.
     *
     * @return string
     */
    public function baseCurrency(): string;
}
