<?php

namespace App\Contracts\Services;
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
     * Format the request url with the given period.
     *
     * @return string
     */
    public function requestUrl(): string;
}
