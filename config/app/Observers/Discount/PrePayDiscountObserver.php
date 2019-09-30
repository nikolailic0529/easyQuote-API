<?php namespace App\Observers\Discount;

use App\Models\Quote\Discount\PrePayDiscount;

class PrePayDiscountObserver
{
    /**
     * Handle the PrePayDiscount "saving" event.
     *
     * @param PrePayDiscount $prePayDiscount
     * @return void
     */
    public function saving(PrePayDiscount $prePayDiscount)
    {
        if($this->exists($prePayDiscount)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
    }

    private function exists(PrePayDiscount $prePayDiscount)
    {
        $user = $prePayDiscount->user;

        return $user->prePayDiscounts()
            ->where('id', '!=', $prePayDiscount->id)
            ->where('country_id', $prePayDiscount->country_id)
            ->where('vendor_id', $prePayDiscount->vendor_id)
            ->whereJsonContains('durations', $prePayDiscount->durations)
            ->exists();
    }
}
