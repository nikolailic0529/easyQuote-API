<?php

namespace App\Traits\Quote;
use Str;

trait HasPricesAttributes
{
    public function initializeHasPricesAttributes()
    {
        $this->fillable = array_merge($this->fillable, ['calculate_list_price', 'buy_price']);
        $this->appends = array_merge($this->appends, ['list_price']);
    }

    public function getListPriceAttribute()
    {
        return Str::decimal($this->getAttribute('totalPrice'), 3);
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::prepend($this->getAttribute('listPrice'), $this->quoteTemplate->currency_symbol, true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) $this->totalPrice - (float) $this->applicable_discounts;
    }

    public function getFinalPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price'), 3), $this->quoteTemplate->currency_symbol, true);
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->applicable_discounts, 3), $this->quoteTemplate->currency_symbol);
    }
}
