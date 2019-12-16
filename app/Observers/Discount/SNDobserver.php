<?php

namespace App\Observers\Discount;

use App\Models\Quote\Discount\SND;

class SNDobserver
{
    /**
     * Handle the SND "saving" event.
     *
     * @param SND $discount
     * @return void
     */
    public function saving(SND $discount)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($discount), 'DE_01', 409);
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
