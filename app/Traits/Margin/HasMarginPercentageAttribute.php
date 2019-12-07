<?php

namespace App\Traits\Margin;

trait HasMarginPercentageAttribute
{
    public function initializeHasMarginPercentageAttribute()
    {
        $this->appends = array_merge(
            $this->appends,
            [
                'margin_percentage_without_country_margin',
                'margin_percentage_without_discounts',
                'user_margin_percentage'
            ]
        );
    }

    public function getMarginPercentageAttribute(): float
    {
        $totalPrice = $this->getAttribute('totalPrice');

        if ($totalPrice === 0.0) {
            return 0.0;
        }

        return (($totalPrice - $this->buy_price) / $totalPrice) * 100;
    }

    public function getBottomUpDividerAttribute(): float
    {
        return 1 - (($this->marginPercentage - $this->custom_discount) / 100);
    }

    public function getReverseMultiplierAttribute(): float
    {
        if ($this->getAttribute('totalPrice') === 0.0) {
            return 1;
        }

        return $this->getAttribute('finalPrice') / $this->getAttribute('totalPrice');
    }

    public function getMarginPercentageWithoutCountryMarginAttribute()
    {
        return round($this->marginPercentage - $this->discounts_sum, 2);
    }

    public function getMarginPercentageWithoutDiscountsAttribute()
    {
        return round($this->marginPercentage + $this->country_margin_value, 2);
    }

    public function getUserMarginPercentageAttribute()
    {
        return round($this->marginPercentage, 2);
    }
}
