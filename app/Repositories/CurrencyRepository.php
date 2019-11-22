<?php

namespace App\Repositories;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Data\Currency;
use Setting;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    protected $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function all()
    {
        $base_currency = Setting::get('base_currency');
        return cache()->sear("all-currencies:{$base_currency}", function () {
            return $this->currency->ordered()->get();
        });
    }
}
