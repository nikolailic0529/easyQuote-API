<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\MultiYearDiscount;

class MultiYearDiscountObserver
{
    /**
     * Handle the MultiYearDiscount "saving" event.
     *
     * @param MultiYearDiscount $discount
     * @return void
     */
    public function saving(MultiYearDiscount $discount)
    {
        //
    }
}
