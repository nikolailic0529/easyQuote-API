<?php namespace App\Observers\Discount;

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
        if(app()->runningInConsole()) {
            return;
        }

        if($this->exists($multiYearDiscount)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
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
