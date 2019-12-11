<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\SND;

class SNDobserver
{
    /**
     * Handle the SND "saving" event.
     *
     * @param SND $snd
     * @return void
     */
    public function saving(SND $snd)
    {
        if (app()->runningInConsole()) {
            return;
        }

        abort_if($this->exists($snd), 409, __('discount.exists_exception'));
    }

    private function exists(SND $snd)
    {
        return $snd
            ->query()
            ->where('id', '!=', $snd->id)
            ->where('vendor_id', $snd->vendor_id)
            ->where('value', $snd->value)
            ->exists();
    }
}
