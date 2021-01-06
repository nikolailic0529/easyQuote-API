<?php

namespace App\Traits\Quote;

use App\Services\QuoteQueries;
use Illuminate\Support\Str;

trait HasPricesAttributes
{
    /** @var float */
    protected float $applicableDiscounts = 0;

    /** @var float */
    protected ?float $totalPrice = null;

    public function initializeHasPricesAttributes()
    {
        $this->mergeFillable(['calculate_list_price', 'buy_price']);
    }

    public function getBuyPriceAttribute($value): float
    {
        return $this->convertExchangeRate((float) $value);
    }

    public function getTotalPriceAttribute(): float
    {
        if (isset($this->totalPrice)) {
            return $this->totalPrice;
        }

        return $this->totalPrice = (new QuoteQueries)
            ->mappedSelectedRowsQuery($this)
            ->sum('price');
    }

    public function setTotalPriceAttribute(float $value): void
    {
        $this->totalPrice = $value;
    }

    public function getListPriceAttribute(): string
    {
        return Str::decimal($this->getAttribute('totalPrice'), 2);
    }

    public function getListPriceFormattedAttribute(): string
    {
        return Str::prepend($this->getAttribute('listPrice'), $this->currencySymbol, true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) $this->totalPrice - (float) $this->applicable_discounts;
    }

    public function getFinalPriceFormattedAttribute(): string
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price'), 2), $this->currencySymbol, true);
    }

    public function getApplicableDiscountsFormattedAttribute(): string
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
