<?php namespace App\Traits\Quote;

trait HasPricesAttributes
{
    public function getListPriceAttribute()
    {
        return round((float) ($this->list_price ?? 0), 2);
    }

    public function getListPriceFormattedAttribute()
    {
        return number_format($this->getAttribute('list_price'), 2);
    }

    public function getFinalPriceAttribute()
    {
        $final_price = ((float) $this->getAttribute('list_price')) - ((float) $this->getAttribute('applicable_discounts'));

        return number_format($final_price, 2);
    }
}
