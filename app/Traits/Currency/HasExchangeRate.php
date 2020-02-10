<?php

namespace App\Traits\Currency;

use App\Models\Data\ExchangeRate;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasExchangeRate
{
    public function exchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class)->orderByDesc('date')->withDefault();
    }
}
