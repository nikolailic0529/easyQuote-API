<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateCollection;
use App\Domain\ExchangeRate\DataTransferObjects\ExchangeRateData;
use App\Domain\ExchangeRate\Events\ExchangeRatesUpdated;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Foundation\Filesystem\Exceptions\FileException;
use App\Foundation\Log\Contracts\LoggerAware;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ExchangeRateService implements \App\Domain\ExchangeRate\Contracts\ManagesExchangeRates, LoggerAware
{
    public function __construct(protected ConnectionResolverInterface $connection,
                                protected Dispatcher $eventsDispatcher,
                                protected ValidatorInterface $validator,
                                protected LoggerInterface $logger = new NullLogger())
    {
    }

    /**
     * @throws \App\Foundation\Filesystem\Exceptions\FileException
     * @throws FileNotFoundException
     */
    public function updateRatesFromFile(string $filepath, Carbon $date): bool
    {
        if (!file_exists($filepath)) {
            throw FileException::notFound($filepath);
        }

        $data = $this->parseRatesData(File::get($filepath, true), $date);

        $this->createRates($data);

        $this->eventsDispatcher->dispatch(new ExchangeRatesUpdated());

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

        $this->eventsDispatcher->dispatch(new ExchangeRatesUpdated());

        return true;
    }

    public function getBaseRate(Currency $source, ?\DateTimeInterface $dateTime = null): float
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

                return (float) ($exchangeRate ?? 1.0);
            }),
            default => $source->exchangeRate->exchange_rate,
        };

        return 1 / $exchangeRate;
    }

    public function getBaseRateByCurrencyCode(string $currencyCode,
                                              ?\DateTimeInterface $dateTime = null,
                                              int $mode = 0): float
    {
        return 1 / $this->getRateByCurrencyCode($currencyCode, $dateTime, $mode);
    }

    public function getRateByCurrencyCode(string $currencyCode,
                                          ?\DateTimeInterface $dateTime = null,
                                          int $mode = 0): float
    {
        if ($currencyCode === $this->baseCurrency()) {
            return 1;
        }

        /** @var ExchangeRate|null $exchangeRate */
        $exchangeRate = ExchangeRate::query()
            ->where('currency_code', $currencyCode)
            ->where('base_currency', $this->baseCurrency())
            ->orderByDesc('date')
            ->when(null !== $dateTime, static function (Builder $builder) use ($dateTime): void {
                $carbon = Carbon::createFromTimestamp($dateTime->getTimestamp());

                $builder->whereBetween('date', [$carbon->clone()->startOfMonth(), $carbon->clone()->endOfMonth()]);
            })
            ->firstOr(callback: function () use ($currencyCode, $mode): ?ExchangeRate {
                if (($mode & static::FALLBACK_LATEST) === self::FALLBACK_LATEST) {
                    return ExchangeRate::query()
                        ->where('currency_code', $currencyCode)
                        ->where('base_currency', $this->baseCurrency())
                        ->latest('date')
                        ->first();
                }

                return null;
            });

        return (float) ($exchangeRate?->exchange_rate ?? 1);
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

        return tap(new ExchangeRate(), function (ExchangeRate $exchangeRate) use ($data) {
            $exchangeRate->currency_code = $data->currency_code;
            $exchangeRate->base_currency = $this->baseCurrency();
            $exchangeRate->date = $data->date;
            $exchangeRate->exchange_rate = $data->exchange_rate;
            $exchangeRate->country_id = $data->country_id;
            $exchangeRate->currency_id = $data->currency_id;

            $this->connection->connection()->transaction(fn () => $exchangeRate->save());
        });
    }

    protected static function parseDates(array $dates): array
    {
        return array_map(Carbon::parse(...), $dates);
    }

    protected static function requestError(string $url)
    {
        throw new \RuntimeException(sprintf(ER_RECEIVE_ERR_01, $url));
    }

    protected function fetchRatesError()
    {
        throw new \RuntimeException(ER_PARSE_ERR_01);
    }

    protected static function fetchDateError(string $filepath)
    {
        throw new \RuntimeException(sprintf("%s Filepath: '%s'.", ER_DT_ERR_01, $filepath));
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn () => $this->logger = $logger);
    }
}
