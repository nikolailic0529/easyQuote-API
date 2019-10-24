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
        return $promotionalDiscount
            ->query()
            ->userCollaboration()
            ->where('id', '!=', $promotionalDiscount->id)
            ->where('vendor_id', $promotionalDiscount->vendor_id)
            ->where('value', $promotionalDiscount->value)
            ->exists();
    }
}
