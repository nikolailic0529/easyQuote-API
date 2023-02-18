<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Services\CurrencyDataMapper;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerCurrencyExchangeRatesListIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerCurrencyIntegration;
use App\Domain\Pipeliner\Integration\Models\CurrencyEntity;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class PushCurrencyStrategy implements Contracts\PushStrategy
{
    use SalesUnitsAware;

    private readonly \DateInterval $cacheTtl;

    private ?array $currencyCache = null;
    private ?array $exchangeRatesListCache = null;

    public function __construct(
        protected CurrencyDataMapper $dataMapper,
        protected PipelinerCurrencyIntegration $currencyIntegration,
        protected PipelinerCurrencyExchangeRatesListIntegration $exchangeRatesListIntegration,
        protected Cache $cache,
        protected LockProvider $lockProvider,
        \DateInterval $cacheTtl = null,
    ) {
        $this->cacheTtl = $cacheTtl ?? CarbonInterval::hours(8);
    }

    public function sync(Model $model): void
    {
        if (!$model instanceof Currency) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Currency::class));
        }

        $currenciesCache = $this->getCurrenciesFromCache();

        // Pass when currency code already exists in the cache.
        if (key_exists($model->code, $currenciesCache)) {
            return;
        }

        $exchangeRatesList = $this->getExchangeRatesListFromCache();

        $baseCurrencyEntity = collect($this->currencyCache)->sole('isBase', true);

        $input = $this->dataMapper->mapPipelinerCreateCurrencyInput(
            currency: $model,
            baseCurrencyEntity: $baseCurrencyEntity,
            exchangeRatesLists: $exchangeRatesList
        );

        $this->currencyIntegration->create($input);

        $this->forgetCurrenciesCache();
    }

    private function getCurrenciesFromCache(): array
    {
        return $this->cache->remember(
            key: $this->getCurrenciesCacheKey(),
            ttl: $this->cacheTtl,
            callback: function (): array {
                return collect($this->currencyIntegration->getAll())
                    ->keyBy(static fn (CurrencyEntity $entity): string => $entity->code)
                    ->all();
            }
        );
    }

    private function getExchangeRatesListFromCache(): array
    {
        return $this->cache->remember(
            key: $this->getExchangeRatesCacheKey(),
            ttl: $this->cacheTtl,
            callback: function (): array {
                return $this->exchangeRatesListIntegration->getAll();
            }
        );
    }

    private function forgetCurrenciesCache(): void
    {
        $this->cache->forget($this->getCurrenciesCacheKey());
    }

    private function getCurrenciesCacheKey(): string
    {
        return static::class.':currencies-list';
    }

    private function getExchangeRatesCacheKey(): string
    {
        return static::class.':exchange-rates-list';
    }

    public function countPending(): int
    {
        return LazyCollection::make(function (): \Generator {
            yield from $this->iteratePending();
        })
            ->count();
    }

    public function iteratePending(): \Traversable
    {
        return Currency::query()
            ->lazyById(100)
            ->filter(function (Currency $currency): bool {
                $this->getCurrenciesFromCache();

                return key_exists($currency->code, $this->currencyCache) === false;
            });
    }

    public function getModelType(): string
    {
        return (new Currency())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Currency;
    }

    public function getByReference(string $reference): object
    {
        return Currency::query()->findOrFail($reference);
    }
}
