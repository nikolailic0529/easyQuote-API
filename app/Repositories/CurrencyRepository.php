<?php namespace App\Repositories;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Models\Data\Currency;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    protected $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function all()
    {
        return Cache::rememberForever('all-currencies', function () {
            return $this->ordered()->get();
        });
    }
}
