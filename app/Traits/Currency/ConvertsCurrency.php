<?php

namespace App\Traits\Currency;

use App\Models\Data\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ConvertsCurrency
{
    protected function initializeConvertsCurrency()
    {
        $this->fillable = array_merge($this->fillable, ['source_currency_id', 'target_currency_id']);
    }

    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }
}
