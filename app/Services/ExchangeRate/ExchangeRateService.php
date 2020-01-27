<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Services\ExchangeRateServiceInterface;
use App\Contracts\Repositories\{
    ExchangeRateRepositoryInterface as Repository,
    CountryRepositoryInterface as Countries,
    CurrencyRepositoryInterface as Currencies
};
use App\Events\ExchangeRatesUpdated;
use App\Models\Data\ExchangeRate;
use GuzzleHttp\{
    Client,
    RequestOptions
};
use Carbon\Carbon;
use SimpleXMLElement;
use Arr;

abstract class ExchangeRateService implements ExchangeRateServiceInterface
{
    /** @var \GuzzleHttp\Client */
    protected $httpClient;

    /** @var \App\Contracts\Repositories\ExchangeRateRepositoryInterface */
    protected $repository;

    /** @var \App\Contracts\Repositories\CountryRepositoryInterface */
    protected $countries;

    /** @var \App\Contracts\Repositories\CurrencyRepositoryInterface */
    protected $currencies;

    /** @var \Carbon\Carbon */
    protected $time;

    public function __construct(
        Client $httpClient,
        Repository $repository,
        Countries $countries,
        Currencies $currencies
    ) {
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->countries = $countries;
        $this->currencies = $currencies;
    }

    public function parseRates(): SimpleXMLElement
    {
        $this->time = $this->requestTime();
        $requestUrl = $this->requestUrl();

        try {
            $response = $this->httpClient->get($requestUrl, [RequestOptions::IDN_CONVERSION => false]);
            $content = $response->getBody()->getContents();

            return \simplexml_load_string($content);
        } catch (\Throwable $exception) {
            throw new \Exception(
                sprintf(ER_PARSE_ERROR_01, $requestUrl)
            );
        }
    }

    public function updateRates(): bool
    {
        $rates = $this->parseRates();
        $rates = collect(iterator_to_array($rates, false));

        $created = \DB::transaction(function () use ($rates) {
           return $rates->reduce(function ($created, $rate) {
                $created[] = $this->storeRate($rate);

                return $created;
            }, []);
        }, 3);

        return tap(count($rates) === count($created), function ($updated) {
            $updated && event(new ExchangeRatesUpdated);
        });
    }

    protected function storeRate(SimpleXMLElement $rate): ExchangeRate
    {
        $attributes = $values = $this->prepareAttributes($rate);

        $attributes = Arr::only($attributes, ['currency_code', 'date']);

        return $this->repository->firstOrCreate($attributes, $values);
    }

    abstract protected function prepareAttributes(SimpleXMLElement $rate): array;

    abstract protected function requestTime(): Carbon;
}
