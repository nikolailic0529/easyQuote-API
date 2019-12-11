<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\PromotionalDiscount;

class PromotionalDiscountObserver
{
    /**
     * Handle the PromotionalDiscount "saving" event.
     *
     * @param PromotionalDiscount $promotionalDiscount
     * @return void
     */
    public function saving(PromotionalDiscount $promotionalDiscount)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($this->exists($promotionalDiscount), 409, __('discount.exists_exception'));
    }

    private function exists(PromotionalDiscount $promotionalDiscount)
    {
        return $promotionalDiscount
            ->query()
            ->where('id', '!=', $promotionalDiscount->id)
            ->where('vendor_id', $promotionalDiscount->vendor_id)
            ->where('value', $promotionalDiscount->value)
            ->exists();
    }
}
