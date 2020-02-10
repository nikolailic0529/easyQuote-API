<?php

namespace App\Services\ExchangeRate;
use Carbon\Carbon;
use SimpleXMLElement;

class HMRCRates extends ExchangeRateService
{
    public function requestUrl(): string
    {
        $date = $this->time;
        $month = static::normilizeDatePeriod($date->month);
        $year = static::normilizeDatePeriod($date->year);

        return strtr(HMRCOptions::SERVICE_URL, ['{m}' => $month, '{y}' => $year]);
    }

    public function baseCurrency(): string
    {
        return 'GBP';
    }

    protected function prepareAttributes(SimpleXMLElement $rate): array
    {
        $countryCode = (string) $rate->{HMRCOptions::XML_ATTR_COUNTRY_CODE};
        $currencyCode = (string) $rate->{HMRCOptions::XML_ATTR_CURRENCY_CODE};
        $exchangeRate = (float) $rate->{HMRCOptions::XML_ATTR_EXCHANGE_RATE};

        return [
            'country_id'    => $this->countries->findIdByCode($countryCode),
            'currency_id'   => $this->currencies->findIdByCode($currencyCode),
            'currency_code' => $currencyCode,
            'date'          => (string) $this->time,
            'exchange_rate' => $exchangeRate
        ];
    }

    protected function requestTime(): Carbon
    {
        return Carbon::now()->startOfMonth();
    }

    private static function normilizeDatePeriod(int $value): string
    {
        $value = (string) (strlen($value) < 2 ? '0' . $value : $value);

        return substr($value, -2);
    }
}
