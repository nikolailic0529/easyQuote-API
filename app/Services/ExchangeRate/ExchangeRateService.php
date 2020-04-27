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
use GuzzleHttp\{
    Client,
    RequestOptions
};
use Illuminate\Support\{
    Collection,
    Facades\Validator,
};
use Carbon\Carbon;
use RuntimeException;
use Arr, DB, File;

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

    public function receiveRates(?Carbon $date = null)
    {
        $date ??= $this->requestTime();

        $this->time = $date;

        return $this->requestRatesByTime($date);
    }

    public function updateRatesFromFile(string $filepath, Carbon $date): bool
    {
        throw_unless(File::exists($filepath), RuntimeException::class, ER_FNE_01);

        $this->time = $date;

        $data = $this->fetchRates(File::get($filepath, true));

        $this->createRates($data);

        event(new ExchangeRatesUpdated);

        return true;
    }

    public function updateRates(array $parameters = []): bool
    {
        if (empty($parameters)) {
            $data = $this->fetchRates($this->receiveRates());
        } else {
            $data = static::parseDates($parameters)
                ->map(fn (Carbon $date) => $this->fetchRates($this->receiveRates($date)))
                ->collapse()->toArray();
        }

        $this->createRates($data);

        event(new ExchangeRatesUpdated);

        return true;
    }

    public function getBaseRate(Currency $source): float
    {
        if ($source->isServiceBaseCurrency()) {
            return 1;
        }

        return 1 / $source->exchangeRate->exchange_rate;
    }

    public function getTargetRate(Currency $source, Currency $target, ?int $precision = null): float
    {
        if ($source->isServiceBaseCurrency()) {
            return $target->exchangeRate->exchange_rate;
        }

        if ($target->isNotServiceBaseCurrency() && !$target->exchangeRate->exists) {
            return 1;
        }

        $rate = $target->exchangeRate->exchange_rate * (1 / $source->exchangeRate->exchange_rate);

        if (is_int($precision)) {
            return round($rate, $precision);
        }

        return $rate;
    }

    protected function createRates($data)
    {
        return DB::transaction(
            fn () =>
            Collection::wrap($data)->map(fn ($attributes) => $this->storeRate($attributes))
        );
    }

    protected function storeRate($attributes)
    {
        $values = $attributes;

        if (!$this->validateRateAttributes($attributes)) {
            return;
        }

        $attributes = Arr::only($attributes, ['currency_code', 'date']) + ['base_currency' => $this->baseCurrency()];

        $values = $attributes + $values;

        return $this->repository->firstOrCreate($attributes, $values);
    }

    protected function validateRateAttributes(array $attributes): bool
    {
        $validator = Validator::make($attributes, [
            'currency_id' => 'required'
        ]);

        return !$validator->fails();
    }

    protected function requestRatesByTime(Carbon $time)
    {
        $this->time = $time;
        $requestUrl = $this->requestUrl();

        try {
            $response = $this->httpClient->get($requestUrl, [RequestOptions::IDN_CONVERSION => false]);
            $content = $response->getBody()->getContents();

            return $content;
        } catch (\Throwable $e) {
            static::requestError($requestUrl);
        }
    }

    abstract public function retrieveDateFromFile(string $filepath): Carbon;

    abstract protected function requestTime(): Carbon;

    protected static function parseDates(array $dates): Collection
    {
        return collect($dates)->map(fn ($date) => Carbon::parse($date));
    }

    /**
     * @throws \RuntimeException
     */
    protected static function requestError(string $url)
    {
        throw new RuntimeException(sprintf(ER_RECEIVE_ERR_01, $url));
    }

    /**
     * @throws \RuntimeException
     */
    protected function fetchRatesError()
    {
        throw new RuntimeException(ER_PARSE_ERR_01);
    }

    /**
     * @throws \RuntimeException
     */
    protected static function fetchDateError(string $filepath)
    {
        throw new RuntimeException(sprintf("%s Filepath: '%s'.", ER_DT_ERR_01, $filepath));
    }
}
