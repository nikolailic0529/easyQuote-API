<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerCurrencyExchangeRatesListIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerCurrencyIntegration;
use App\Integrations\Pipeliner\Models\CurrencyEntity;
use App\Models\Data\Currency;
use App\Services\Currency\CurrencyDataMapper;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class PushCurrencyStrategy implements Contracts\PushStrategy
{
    use SalesUnitsAware;

    private ?array $currencyCache = null;
    private ?array $exchangeRatesListCache = null;

    public function __construct(protected CurrencyDataMapper                            $dataMapper,
                                protected PipelinerCurrencyIntegration                  $currencyIntegration,
                                protected PipelinerCurrencyExchangeRatesListIntegration $exchangeRatesListIntegration)
    {
    }

    public function sync(Model $model): void
    {
        if (!$model instanceof Currency) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Currency::class));
        }

        $this->ensureCurrencyCacheLoaded();

        // Pass when currency code already exists in the cache.
        if (key_exists($model->code, $this->currencyCache)) {
            return;
        }

        $this->ensureExchangeRatesListCacheLoaded();

        $baseCurrencyEntity = collect($this->currencyCache)->sole('isBase', true);

        $input = $this->dataMapper->mapPipelinerCreateCurrencyInput($model, $baseCurrencyEntity, $this->exchangeRatesListCache);

        $entity = $this->currencyIntegration->create($input);

        $this->currencyCache[$entity->code] = $entity;
    }

    private function ensureCurrencyCacheLoaded(): void
    {
        $this->currencyCache ??= collect($this->currencyIntegration->getAll())
            ->keyBy(static fn(CurrencyEntity $entity): string => $entity->code)
            ->all();
    }

    private function ensureExchangeRatesListCacheLoaded(): void
    {
        $this->exchangeRatesListCache ??= $this->exchangeRatesListIntegration->getAll();
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
                $this->ensureCurrencyCacheLoaded();

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