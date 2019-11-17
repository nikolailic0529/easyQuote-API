<?php

namespace App\Traits\Discount;

use App\Models\Quote\{
    Discount,
    Discount\MultiYearDiscount,
    Discount\PrePayDiscount,
    Discount\PromotionalDiscount,
    Discount\SND
};
use Arr;

trait HasMorphableDiscounts
{
    public function getDiscountsSumAttribute()
    {
        if (!isset($this->discounts)) {
            $this->load('discounts')->withPivot(['duration'])->with('discountable');
        }

        return $this->discounts
            ->reduce(function ($carry, $discount) {
                return $carry + $discount->getValue($this->list_price);
            }, 0) + $this->custom_discount;
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'quote_discount')
            ->withPivot('duration', 'margin_percentage')
            ->with('discountable')
            ->whereHasMorph('discountable', $this->discountsOrder())
            ->orderByRaw("field(`discounts`.`discountable_type`, {$this->discountsOrderToString()})", 'desc');
    }

    public function discountsOrder(): array
    {
        return [
            MultiYearDiscount::class,
            PrePayDiscount::class,
            PromotionalDiscount::class,
            SND::class
        ];
    }

    public function discountsOrderToString(): string
    {
        return Arr::quote($this->discountsOrder());
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return number_format($this->applicable_discounts ?? 0, 2);
    }

    public function getApplicableDiscountsAttribute()
    {
        return round((float) ($this->applicable_discounts ?? 0), 2);
    }
}
