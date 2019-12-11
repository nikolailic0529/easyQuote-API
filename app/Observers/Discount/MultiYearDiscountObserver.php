<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\MultiYearDiscount;

class MultiYearDiscountObserver
{
    /**
     * Handle the MultiYearDiscount "saving" event.
     *
     * @param MultiYearDiscount $multiYearDiscount
     * @return void
     */
    public function saving(MultiYearDiscount $multiYearDiscount)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($this->exists($multiYearDiscount), 409, __('discount.exists_exception'));
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
