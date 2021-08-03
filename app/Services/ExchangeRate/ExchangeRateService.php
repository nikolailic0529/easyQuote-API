<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Services\ManagesExchangeRates;
use App\DTO\ExchangeRate\ExchangeRateCollection;
use App\DTO\ExchangeRate\ExchangeRateData;
use App\Events\ExchangeRatesUpdated;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use App\Services\Exceptions\FileException;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ExchangeRateService implements ManagesExchangeRates
{
    protected ConnectionInterface $connection;

    protected Dispatcher $eventsDispatcher;

    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, Dispatcher $eventsDispatcher, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->eventsDispatcher = $eventsDispatcher;
        $this->validator = $validator;
    }

    /**
     * @param string $filepath
     * @param Carbon $date
     * @return bool
     * @throws FileException
     * @throws FileNotFoundException
     */
    public function updateRatesFromFile(string $filepath, Carbon $date): bool
    {
        if (!file_exists($filepath)) {
            throw FileException::notFound($filepath);
        }

        $data = $this->parseRatesData(File::get($filepath, true), $date);

        $this->createRates($data);

        $this->eventsDispatcher->dispatch(new ExchangeRatesUpdated);

        return true;
    }

    public function updateRates(array $parameters = []): bool
    {
        /** @var ExchangeRateCollection $data */
        $data = with($parameters, function (array $parameters) {
            if (empty($parameters)) {
                return $this->getRatesData(now());
            }

            $collection = collect(static::parseDates($parameters))
                ->reduce(function (array $fetchedRates, Carbon $date) {
                    $collection = $this->getRatesData($date);

                    $ratesArray = [];

                    foreach ($collection as $rateData) {
                        $ratesArray[] = $rateData;
                    }

                    return array_merge($fetchedRates, $ratesArray);
                }, []);

            return new ExchangeRateCollection($collection);
        });

        $this->createRates($data);

        $this->eventsDispatcher->dispatch(new ExchangeRatesUpdated);

        return true;
    }

    public function getBaseRate(Currency $source, ?DateTimeInterface $dateTime = null): float
    {
        if ($source->code === $this->baseCurrency()) {
            return 1;
        }

        $exchangeRate = match (true) {
            false === is_null($dateTime) => value(function () use ($dateTime, $source): float {
                $exchangeRate = $source->exchangeRate()
                    ->where(function (Builder $builder) use ($dateTime) {
                        $builder->where('date', '>=', Carbon::instance($dateTime)->startOfMonth())
                            ->where('date', '<=', Carbon::instance($dateTime)->endOfMonth());
                    })
                    ->value('exchange_rate');

                return (float)($exchangeRate ?? 1.0);
            }),
            default => $source->exchangeRate->exchange_rate,
        };

        return 1 / $exchangeRate;
    }

    public function getBaseRateByCurrencyCode(string $currencyCode, ?DateTimeInterface $dateTime = null): float
    {
        if ($currencyCode === $this->baseCurrency()) {
            return 1;
        }

        $exchangeRateValue = ExchangeRate::query()
            ->where('currency_code', $currencyCode)
            ->where('base_currency', $this->baseCurrency())
            ->orderBy('date', 'desc')
            ->when(!is_null($dateTime), function (Builder $builder) use ($dateTime) {
                $carbon = Carbon::createFromTimestamp($dateTime->getTimestamp());

                $builder->whereBetween('date', [$carbon->clone()->startOfMonth(), $carbon->clone()->endOfMonth()]);
            })
            ->value('exchange_rate');

        $exchangeRateValue ??= 1;

        return 1 / $exchangeRateValue;
    }

    public function getTargetRate(Currency $source, Currency $target, ?int $precision = null): float
    {
        if ($source->code === $this->baseCurrency()) {
            return $target->exchangeRate->exchange_rate;
        }

        if ($target->code !== $this->baseCurrency() && !$target->exchangeRate->exists) {
            return 1;
        }

        $rate = $target->exchangeRate->exchange_rate * (1 / $source->exchangeRate->exchange_rate);

        if (!is_null($precision)) {
            return round($rate, $precision);
        }

        return $rate;
    }

    protected function createRates(ExchangeRateCollection $data): void
    {
        $data->rewind();

        foreach ($data as $rate) {
            $this->storeRate($rate);
        }
    }

    protected function storeRate(ExchangeRateData $data): ?ExchangeRate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            // TODO: log the violations.
            return null;
        }

        /** @var ExchangeRate|null $exchangeRate */
        $exchangeRate = ExchangeRate::query()->where('currency_code', $data->currency_code)
            ->where('date', $data->date)
            ->where('base_currency', $this->baseCurrency())
            ->first();

        if (!is_null($exchangeRate)) {
            return $exchangeRate;
        }

        return tap(new ExchangeRate(), function (ExchangeRate $exchangeRate) use ($data, $violations) {
            $exchangeRate->currency_code = $data->currency_code;
            $exchangeRate->base_currency = $this->baseCurrency();
            $exchangeRate->date = $data->date;
            $exchangeRate->exchange_rate = $data->exchange_rate;
            $exchangeRate->country_id = $data->country_id;
            $exchangeRate->currency_id = $data->currency_id;

            $this->connection->transaction(fn() => $exchangeRate->save());
        });
    }

    protected static function parseDates(array $dates): array
    {
        return array_map(function ($date) {
            return Carbon::parse($date);
        }, $dates);
    }

    /**
     * @param string $url
     */
    protected static function requestError(string $url)
    {
        throw new RuntimeException(sprintf(ER_RECEIVE_ERR_01, $url));
    }

    protected function fetchRatesError()
    {
        throw new RuntimeException(ER_PARSE_ERR_01);
    }

    /**
     * @param string $filepath
     */
    protected static function fetchDateError(string $filepath)
    {
        throw new RuntimeException(sprintf("%s Filepath: '%s'.", ER_DT_ERR_01, $filepath));
    }
}
