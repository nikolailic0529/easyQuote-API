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
        if(app()->runningInConsole()) {
            return;
        }

        if($this->exists($prePayDiscount)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
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
