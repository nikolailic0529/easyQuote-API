<?php namespace App\Traits\Margin;

trait HasMarginPercentageAttribute
{
    public function getMarginPercentageAttribute()
    {
        return round($this->user_margin_percentage + $this->country_margin_value - $this->discounts_sum, 2);
    }

    public function getMarginPercentageWithoutCountryMarginAttribute()
    {
        return round($this->user_margin_percentage - $this->discounts_sum, 2);
    }

    public function getMarginPercentageWithoutDiscountsAttribute()
    {
        return round($this->user_margin_percentage + $this->country_margin_value, 2);
    }

    public function getUserMarginPercentageAttribute()
    {
        return round((float) $this->attributes['margin_percentage'], 2);
    }
}
