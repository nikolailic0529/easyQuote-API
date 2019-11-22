<?php

namespace App\Traits\Margin;

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

    public function calculateMarginPercentage()
    {
        $this->list_price = $this->countTotalPrice();

        $margin_percentage = (float) $this->list_price !== 0.0
            ? round((($this->list_price - $this->buy_price) / $this->list_price) * 100, 2)
            : 0.0;

        $this->promiseNotRecalculateMargin();

        $this->forceFill(compact('margin_percentage'))->save();
    }

    public function promiseRecalculateMargin()
    {
        $this->shouldRecalculateMargin = true;
    }

    public function promiseNotRecalculateMargin()
    {
        $this->shouldRecalculateMargin = false;
    }
}
