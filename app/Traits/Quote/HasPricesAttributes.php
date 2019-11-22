<?php

namespace App\Traits\Quote;
use Str;

trait HasPricesAttributes
{
    public function getListPriceAttribute()
    {
        return (float) $this->list_price ?? 0;
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('list_price')), $this->quoteTemplate->currency_symbol);
    }

    public function getFinalPriceAttribute()
    {
        return $this->getAttribute('list_price') - $this->getAttribute('applicable_discounts');
    }

    public function getFinalPriceFormattedAttribute()
    {
        return Str::prepend(Str::decimal($this->getAttribute('final_price')), $this->quoteTemplate->currency_symbol);
    }
}
