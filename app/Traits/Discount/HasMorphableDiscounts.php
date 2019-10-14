<?php namespace App\Traits\Discount;

use App\Models\Quote\Discount;

trait HasMorphableDiscounts
{
    public function getDiscountsSumAttribute()
    {
        if(!isset($this->discounts)) {
            $this->load('discounts')->withPivot('duration')->with('discountable');
        }

        return $this->discounts
            ->reduce(function ($carry, $discount) {
                return $carry + $discount->getValue(0);
            }, 0);
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'quote_discount')
            ->withPivot('duration')
            ->with('discountable')->whereHasMorph('discountable', [
                \App\Models\Quote\Discount\MultiYearDiscount::class,
                \App\Models\Quote\Discount\PrePayDiscount::class,
                \App\Models\Quote\Discount\PromotionalDiscount::class,
                \App\Models\Quote\Discount\SND::class
            ]);
    }
}
