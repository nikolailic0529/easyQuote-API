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
        //
    }
}
