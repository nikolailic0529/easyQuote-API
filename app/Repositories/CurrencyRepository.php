<?php

namespace App\Repositories;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Data\Currency;
use Setting;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    const CACHE_PREFIX_ALL = 'all-currencies:';

    const CACHE_PREFIX_CODE = 'currency-id-code:';

    /** @var \App\Models\Data\Currency */
    protected $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function all()
    {
        $base_currency = Setting::get('base_currency');

        return cache()->sear(self::CACHE_PREFIX_ALL . $base_currency, function () {
            return $this->currency->ordered()->get();
        });
    }

    public function allHaveExrate()
    {
        return $this->currency->query()
            ->whereHas('exchangeRate')
            ->orWhere('code', app('exchange.service')->baseCurrency())
            ->ordered()
            ->get();
    }

    public function find(string $id): Currency
    {
        return $this->currency->query()->whereId($id)->firstOrFail();
    }

    public function findByCode(?string $code)
    {
        return $this->currency->query()->whereCode($code)->first();
    }

    public function findIdByCode($code)
    {
        if (is_array($code)) {
            return cache()->sear(self::CACHE_PREFIX_CODE . implode(',', $code), function () use ($code) {
                return $this->currency->whereIn('code', $code)->pluck('id', 'code');
            });
        }

        throw_unless(is_string($code), new \InvalidArgumentException(
            sprintf('%s %s given.', INV_ARG_SA_01, gettype($code))
        ));

        return cache()->sear(self::CACHE_PREFIX_CODE . $code, function () use ($code) {
            return $this->currency->whereCode($code)->value('id');
        });
    }

    public function firstOrCreate(array $attributes, array $values = []): Currency
    {
        return $this->currency->firstOrCreate($attributes, $values);
    }
}
