<?php

namespace App\Domain\Rescue\Concerns;

trait HasMarginPercentageAttribute
{
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
        return 1 - (($this->userMarginPercentage - $this->custom_discount) / 100);
    }

    public function getMarginDividerAttribute(): float
    {
        if (!isset($this->countryMargin)) {
            return 1;
        }

        $targetMargin = ($this->countryMargin->value - $this->custom_discount) / 100;

        return $targetMargin >= 1
            /*
             * When target margin is greater than or equal to 100% we are reversing bottom up rule.
             * It will be increasing total price and line prices accordingly.
             */
            ? 1 / ($targetMargin + 1)
            /*
             * When target margin is less than 100% we are using default bottom up rule
             * */
            : 1 - $targetMargin;
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
