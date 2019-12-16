<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\PromotionalDiscount;

class PromotionalDiscountObserver
{
    /**
     * Handle the PromotionalDiscount "saving" event.
     *
     * @param PromotionalDiscount $discount
     * @return void
     */
    public function saving(PromotionalDiscount $discount)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($discount), 'DE_01', 409);
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
