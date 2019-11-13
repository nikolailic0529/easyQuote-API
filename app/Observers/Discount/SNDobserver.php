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

        if ($this->exists($snd)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
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
