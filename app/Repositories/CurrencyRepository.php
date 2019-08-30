<?php

namespace App\Repositories;

use App\Models\Data\Currency;
use App\Contracts\Repositories\CurrencyRepositoryInterface;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function all()
    {
        return Currency::ordered()->get();
    }
}