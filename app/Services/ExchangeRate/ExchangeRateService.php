<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Services\ExchangeRateServiceInterface;
use App\Contracts\Repositories\{
    ExchangeRateRepositoryInterface as Repository,
    CountryRepositoryInterface as Countries,
    CurrencyRepositoryInterface as Currencies
};
use App\Events\ExchangeRatesUpdated;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use GuzzleHttp\{
    Client,
    RequestOptions
};
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Arr;

abstract class ExchangeRateService implements ExchangeRateServiceInterface
{
    /** @var \GuzzleHttp\Client */
    protected Client $httpClient;

    /** @var \App\Contracts\Repositories\ExchangeRateRepositoryInterface */
    protected Repository $repository;

    /** @var \App\Contracts\Repositories\CountryRepositoryInterface */
    protected Countries $countries;

    /** @var \App\Contracts\Repositories\CurrencyRepositoryInterface */
    protected Currencies $currencies;

    /** @var \Carbon\Carbon */
    protected Carbon $time;

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

        return tap(
            (bool) count(array_filter($created)),
            fn ($updated) => $updated && event(new ExchangeRatesUpdated)
        );
    }

    public function getTargetRate(Currency $source, Currency $target, ?int $precision = null): float
    {
        if ($source->isBaseCurrency()) {
            return $target->exchangeRate->exchange_rate;
        }

        if ($target->isNotBaseCurrency() && !$target->exchangeRate->exists) {
            return 1;
        }

        $rate = $target->exchangeRate->exchange_rate * (1 / $source->exchangeRate->exchange_rate);

        if (is_int($precision)) {
            return round($rate, $precision);
        }

        return $rate;
    }

    protected function storeRate(SimpleXMLElement $rate)
    {
        $attributes = $values = $this->prepareAttributes($rate);

        if (!$this->validateRateAttributes($attributes)) {
            return;
        }

        $attributes = Arr::only($attributes, ['currency_code', 'date']);

        $values = ['base_currency' => $this->baseCurrency()] + $values;

        return $this->repository->firstOrCreate($attributes, $values);
    }

    protected function validateRateAttributes(array $attributes): bool
    {
        $validator = Validator::make($attributes, [
            'currency_id' => 'required'
        ]);

        return !$validator->fails();
    }

    abstract protected function prepareAttributes(SimpleXMLElement $rate): array;

    abstract protected function requestTime(): Carbon;
}
