<?php

namespace App\Domain\Discount\Concerns;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Rescue\Models\{
    Discount
};
use Illuminate\Support\Arr;

trait HasMorphableDiscounts
{
    public function getDiscountsSumAttribute()
    {
        if (!isset($this->discounts)) {
            $this->load('discounts')->withPivot(['duration'])->with('discountable');
        }

        return $this->discounts
            ->reduce(fn ($carry, $discount) => $carry + $discount->getValue((float) $this->list_price), 0)
            + $this->custom_discount;
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'quote_discount')
            ->withPivot('duration', 'margin_percentage')
            ->with('discountable')
            ->whereHasMorph('discountable', $this->discountsOrder())
            ->orderByRaw("field(`discounts`.`discountable_type`, {$this->discountsOrderToString()}) desc");
    }

    public function discountsOrder(): array
    {
        return [
            MultiYearDiscount::class,
            PrePayDiscount::class,
            PromotionalDiscount::class,
            \App\Domain\Discount\Models\SND::class,
        ];
    }

    public function discountsOrderToString(): string
    {
        return Arr::quote($this->discountsOrder());
    }
}
