<?php

namespace App\Traits\Quote;
use Str;

trait HasPricesAttributes
{
    public function getListPriceAttribute()
    {
        return round((float) $this->list_price ?? 0, 2);
    }

    public function getListPriceFormattedAttribute()
    {
        return Str::decimal($this->list_price);
    }

    public function getFinalPriceAttribute()
    {
        $final_price = ((float) $this->list_price - (float) $this->applicable_discounts);

        return Str::decimal($final_price);
    }
}
