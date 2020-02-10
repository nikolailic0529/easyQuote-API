<?php

namespace App\Traits\Quote;
use Str;

trait HasPricesAttributes
{
    protected $applicableDiscounts = 0;

    public function initializeHasPricesAttributes()
    {
        $this->fillable = array_merge($this->fillable, ['calculate_list_price', 'buy_price']);
    }

    public function getBuyPriceAttribute($value)
    {
        return $this->convertExchangeRate((float) $value);
    }

    public function getListPriceAttribute()
    {
        return Str::decimal($this->getAttribute('totalPrice'), 2);
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::prepend($this->getAttribute('listPrice'), $this->currencySymbol, true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) $this->totalPrice - (float) $this->applicable_discounts;
    }

    public function getFinalPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price'), 2), $this->currencySymbol, true);
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return Str::prepend(Str::decimal((float) $this->applicable_discounts, 2), $this->currencySymbol);
    }

    public function getApplicableDiscountsAttribute(): float
    {
        return $this->applicableDiscounts;
    }

    public function setApplicableDiscountsAttribute($value): void
    {
        $this->applicableDiscounts = (float) $value;
    }
}
