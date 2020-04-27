<?php

namespace App\Contracts\Services;

use App\Models\Data\Currency;
use Carbon\Carbon;
use SimpleXMLElement;

interface ExchangeRateServiceInterface
{
    /**
     * Receive Exchange Rates data from an external source.
     *
     * @return mixed
     * @throws \Exception
     */
    public function receiveRates();
    
    /**
     * Fetch Exchange Rates data received from an external source.
     * Returns attributes for exchange rates.
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function fetchRates($data): array;

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
     * Retrieve rates date from given file.
     *
     * @param string $filepath
     * @return Carbon
     */
    public function retrieveDateFromFile(string $filepath): Carbon;

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
     * @return float
     */
    public function getBaseRate(Currency $source): float;

    /**
     * Format the request url with the given period.
     *
     * @return string
     */
    public function requestUrl(): string;

    /**
     * Service Base Currency Code.
     *
     * @return string
     */
    public function baseCurrency(): string;
}
