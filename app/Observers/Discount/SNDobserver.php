<?php namespace App\Observers\Discount;

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
        if($this->exists($snd)) {
            throw new \ErrorException(__('discount.exists_exception'));
        }
    }

    private function exists(SND $snd)
    {
        $user = $snd->user;

        return $user->SNDs()
            ->where('id', '!=', $snd->id)
            ->where('country_id', $snd->country_id)
            ->where('vendor_id', $snd->vendor_id)
            ->where('value', $snd->value)
            ->exists();
    }
}
