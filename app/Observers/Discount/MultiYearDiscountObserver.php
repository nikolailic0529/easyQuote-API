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
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($discount), DE_01, 'DE_01', 409);
    }

    private function exists(MultiYearDiscount $multiYearDiscount)
    {
        return $multiYearDiscount
            ->query()
            ->where('id', '!=', $multiYearDiscount->id)
            ->where('vendor_id', $multiYearDiscount->vendor_id)
            ->durationIn($multiYearDiscount->years)
            ->exists();
    }
}
