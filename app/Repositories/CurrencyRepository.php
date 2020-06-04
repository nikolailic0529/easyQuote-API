<?php

namespace App\Repositories;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder as DbBuilder;
use Setting;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    const CACHE_PREFIX_ALL = 'all-currencies:';

    const CURRENCY_ID_CACHE_KEY = 'currency.id';

    const CURRENCY_CODE_CACHE_KEY = 'currency.code';

    protected Currency $currency;

    protected bool $cacheEnabled = true;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function disableCache(): void
    {
        $this->cacheEnabled = false;
    }

    public function enableCache(): void
    {
        $this->cacheEnabled = true;
    }

    public function all()
    {
        $base_currency = Setting::get('base_currency');

        return $this->remember(static::CACHE_PREFIX_ALL . $base_currency, fn () => $this->currency->ordered()->get());
    }

    public function allHaveExrate()
    {
        return $this->currency->query()
            ->whereHas('exchangeRate')
            ->orWhere('code', app('exchange.service')->baseCurrency())
            ->ordered()
            ->get();
    }

    public function findOrFail(string $id): Currency
    {
        return $this->currency->query()->whereKey($id)->firstOrFail();
    }

    public function find(string $id): ?Currency
    {
        return $this->currency->query()->whereKey($id)->first();
    }

    public function findCached(string $id): ?Currency
    {
        return $this->currency->query()->whereKey($id)->cacheForever()->first();
    }

    public function findByCode(?string $code)
    {
        return $this->remember(
            static::currencyCodeCacheKey($code),
            fn () => $this->currency->query()->whereCode($code)->first()
        );
    }

    public function findIdByCode($code)
    {
        $codeKey = implode(',', (array) $code);

        if (is_array($code)) {
            return $this->remember(
                static::currencyIdCacheKey($codeKey),
                fn () => $this->currency->whereIn('code', $code)->pluck('id', 'code')
            );
        }

        throw_unless(is_string($code), new \InvalidArgumentException(
            sprintf('%s %s given.', INV_ARG_SA_01, gettype($code))
        ));

        return $this->remember(
            static::currencyIdCacheKey($codeKey),
            fn () => $this->currency->whereCode($code)->value('id')
        );
    }

    public function firstOrCreate(array $attributes, array $values = []): Currency
    {
        return $this->currency->firstOrCreate($attributes, $values);
    }

    protected function remember($key, $value, $ttl = null)
    {
        if (!$this->cacheEnabled) {
            return value($value);
        }

        if ($cache = Cache::get($key)) {
            return $cache;
        }

        return tap(value($value), fn ($value) => Cache::put($key, $value, $ttl));
    }

    protected static function currencyIdCacheKey(?string $code): string
    {
        return static::CURRENCY_ID_CACHE_KEY . ':' . $code ?: 'null';
    }

    protected static function currencyCodeCacheKey(?string $code): string
    {
        return static::CURRENCY_CODE_CACHE_KEY . ':' . $code ?: 'null';
    }
}
