<?php

namespace App\Domain\Currency\Concerns;

use App\Domain\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCurrency
{
    public function initializeBelongsToCurrency()
    {
        $this->fillable = array_merge($this->fillable, ['currency_id']);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getCurrencySymbolAttribute()
    {
        return $this->currency->symbol ?? null;
    }
}
