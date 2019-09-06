<?php namespace App\Repositories;

use App\Models\Data\Currency;
use App\Contracts\Repositories\CurrencyRepositoryInterface;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    protected $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function all()
    {
        return $this->ordered()->get();
    }
}
