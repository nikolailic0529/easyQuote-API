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
}
