<?php

namespace App\Traits;

use App\Models\Data\Currency;

trait BelongsToCurrency
{
    public function initializeBelongsToCurrency()
    {
        $this->fillable = array_merge($this->fillable, ['currency_id']);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
