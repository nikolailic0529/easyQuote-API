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
        //
    }
}
