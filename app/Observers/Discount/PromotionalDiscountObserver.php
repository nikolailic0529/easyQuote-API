<?php namespace App\Observers\Discount;

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
        if($this->exists($promotionalDiscount)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
    }

    private function exists(PromotionalDiscount $promotionalDiscount)
    {
        $user = $promotionalDiscount->user;

        return $user->promotionalDiscounts()
            ->where('id', '!=', $promotionalDiscount->id)
            ->where('country_id', $promotionalDiscount->country_id)
            ->where('vendor_id', $promotionalDiscount->vendor_id)
            ->where('value', $promotionalDiscount->value)
            ->where('minimum_limit', $promotionalDiscount->minimum_limit)
            ->exists();
    }
}
