<?php

namespace App\Services\ExchangeRate;

use App\DTO\ExchangeRate\ExchangeRateCollection;
use App\DTO\ExchangeRate\ExchangeRateData;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HMRCRates extends ExchangeRateService
{
    public function buildRequestUrl(\DateTimeInterface $dateTime): string
    {
        return strtr(HMRC::SERVICE_URL, ['{m}' => $dateTime->format('m'), '{y}' => $dateTime->format('y')]);
    }

    /**
     * @inheritDoc
     * @throws RequestException
     */
    public function getRatesData(\DateTimeInterface $dateTime): ExchangeRateCollection
    {
        $url = $this->buildRequestUrl($dateTime);

        $xml = Http::get($url)->throw()->body();

        return $this->parseRatesData($xml, $dateTime);
    }

    /**
     * @inheritDoc
     * @throws RequestException
     */
    public function getRateDataOfCurrency(string $currencyCode, \DateTimeInterface $dateTime): ?ExchangeRateData
    {
        $rateCollection = $this->getRatesData($dateTime);

        foreach ($rateCollection as $rateData) {

            if ($rateData->currency_code === $currencyCode) {
                return $rateData;
            }

        }

        return null;

    }

    protected function parseRatesData(string $data, \DateTimeInterface $dateTime): ExchangeRateCollection
    {
        $data = iterator_to_array(simplexml_load_string($data), false);

        $collection = array_map(function (object $rate) use ($dateTime) {
            return $this->parseRateObject($rate, $dateTime);
        }, $data);

        return new ExchangeRateCollection($collection);
    }

    /**
     * @param string $filepath
     * @return Carbon
     * @throws FileNotFoundException
     */
    public function parseRatesDateFromFile(string $filepath): Carbon
    {
        $content = File::get($filepath, true);

        $xml = simplexml_load_string($content);

        $period = (string)data_get($xml->attributes(), 'Period');

        $period = Str::before($period, ' ');

        return Carbon::createFromFormat('d/M/Y', $period);
    }

    public function baseCurrency(): string
    {
        return 'GBP';
    }

    protected function parseRateObject(object $rate, \DateTimeInterface $dateTime): ExchangeRateData
    {
        $countryCode = (string)$rate->{HMRC::XML_ATTR_COUNTRY_CODE};
        $currencyCode = (string)$rate->{HMRC::XML_ATTR_CURRENCY_CODE};
        $exchangeRate = (float)$rate->{HMRC::XML_ATTR_EXCHANGE_RATE};

        return new ExchangeRateData([
            'country_id' => Country::query()->where('iso_3166_2', $countryCode)->value('id'),
            'currency_id' => Currency::query()->where('code', $currencyCode)->value('id'),
            'country_code' => $countryCode,
            'currency_code' => $currencyCode,
            'date' => Carbon::instance($dateTime)->startOfMonth()->format('Y-m-d'),
            'exchange_rate' => $exchangeRate
        ]);
    }
}
