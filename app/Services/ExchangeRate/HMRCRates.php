<?php

namespace App\Services\ExchangeRate;

use Carbon\Carbon;
use File, Str;

class HMRCRates extends ExchangeRateService
{
    public function requestUrl(): string
    {
        $date = $this->time;

        $month = static::normilizeDatePeriod($date->month);
        $year = static::normilizeDatePeriod($date->year);

        return strtr(HMRC::SERVICE_URL, ['{m}' => $month, '{y}' => $year]);
    }

    public function baseCurrency(): string
    {
        return 'GBP';
    }

    public function fetchRates($content): array
    {
        try {
            $data = iterator_to_array(simplexml_load_string($content), false);

            return collect($data)->map(fn ($attributes) => $this->prepareAttributes($attributes))->toArray();
        } catch (Throwable $exception) {
            report_logger(['ErrorCode' => 'ER_PARSE_ERR_01'], ER_PARSE_ERR_01);

            static::fetchRatesError();
        }
    }


    public function retrieveDateFromFile(string $filepath): Carbon
    {
        try {
            $content = File::get($filepath, true);

            $xml = simplexml_load_string($content);

            $period = (string) data_get($xml->attributes(), 'Period');

            $period = Str::before($period, ' ');

            return Carbon::createFromFormat('d/M/Y', $period);
        } catch (Throwable $e) {
            report_logger(
                ['ErrorCode' => 'ER_PARSE_ERR_02'],
                sprintf("%s Filepath: '%s'. Original error: '%s'.", ER_DT_ERR_01, $filepath, $e->getMessage())
            );

            static::fetchDateError($filepath);
        }
    }

    protected function prepareAttributes($rate): array
    {
        $countryCode = (string) $rate->{HMRC::XML_ATTR_COUNTRY_CODE};
        $currencyCode = (string) $rate->{HMRC::XML_ATTR_CURRENCY_CODE};
        $exchangeRate = (float) $rate->{HMRC::XML_ATTR_EXCHANGE_RATE};

        return [
            'country_id'    => $this->countries->findIdByCode($countryCode),
            'currency_id'   => $this->currencies->findIdByCode($currencyCode),
            'country_code'  => $countryCode,
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
        return sprintf('%02d', substr($value, -2));
    }
}
