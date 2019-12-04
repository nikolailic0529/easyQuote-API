<?php

namespace App\Traits\Quote;
use Str;

trait HasPricesAttributes
{
    public function initializeHasPricesAttributes()
    {
        $this->fillable = array_merge($this->fillable, ['calculate_list_price', 'buy_price']);
    }

    public function getListPriceAttribute()
    {
        return Str::decimal($this->list_price);
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->list_price), $this->quoteTemplate->currency_symbol, true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) $this->list_price - (float) $this->applicable_discounts;
    }

    public function getFinalPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price')), $this->quoteTemplate->currency_symbol, true);
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->applicable_discounts), $this->quoteTemplate->currency_symbol);
    }

    public function getBottomUpDividerAttribute(): float
    {
        return 1 - (($this->margin_percentage_without_discounts - $this->custom_discount) / 100);
    }
}
