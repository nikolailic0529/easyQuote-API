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
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($discount), DE_01, 'DE_01', 409);
    }

    private function exists(PrePayDiscount $prePayDiscount)
    {
        return $prePayDiscount
            ->query()
            ->where('id', '!=', $prePayDiscount->id)
            ->where('vendor_id', $prePayDiscount->vendor_id)
            ->durationIn($prePayDiscount->years)
            ->exists();
    }
}
