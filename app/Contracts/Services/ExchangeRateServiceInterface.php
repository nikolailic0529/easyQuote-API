<?php

namespace App\Contracts\Services;

use App\Models\Data\Currency;
use SimpleXMLElement;

interface ExchangeRateServiceInterface
{
    /**
     * Parse Exchange Rates from an external source.
     *
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public function parseRates(): SimpleXMLElement;

    /**
     * Update Exchange Rates in storage.
     *
     * @return boolean
     */
    public function updateRates(): bool;

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
