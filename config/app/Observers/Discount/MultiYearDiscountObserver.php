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
        if($this->exists($multiYearDiscount)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
    }

    private function exists(MultiYearDiscount $multiYearDiscount)
    {
        $user = $multiYearDiscount->user;

        return $user->multiYearDiscounts()
            ->where('id', '!=', $multiYearDiscount->id)
            ->where('country_id', $multiYearDiscount->country_id)
            ->where('vendor_id', $multiYearDiscount->vendor_id)
            ->whereJsonContains('durations', $multiYearDiscount->durations)
            ->exists();
    }
}
