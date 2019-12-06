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
        return Str::decimal($this->getAttribute('totalPrice'));
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->list_price), $this->quoteTemplate->currency_symbol, true);
    }

    public function getFinalPriceAttribute(): float
    {
        if ($this->bottomUpDivider === 0.0) {
            return 0.0;
        }

        $buyPriceAfterBottomUp = $this->buy_price / $this->bottomUpDivider;

        return (float) $buyPriceAfterBottomUp - (float) $this->applicable_discounts;
    }

    public function getFinalPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price')), $this->quoteTemplate->currency_symbol, true);
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->applicable_discounts + (float) $this->applicable_custom_discount), $this->quoteTemplate->currency_symbol);
    }
}
