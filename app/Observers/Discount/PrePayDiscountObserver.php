<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\PrePayDiscount;

class PrePayDiscountObserver
{
    /**
     * Handle the PrePayDiscount "saving" event.
     *
     * @param PrePayDiscount $discount
     * @return void
     */
    public function saving(PrePayDiscount $discount)
    {
        //
    }
}
