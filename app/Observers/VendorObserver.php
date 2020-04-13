<?php

namespace App\Observers;

use App\Models\Vendor;

class VendorObserver
{
    /**
     * Handle the Vendor "saving" event.
     *
     * @param Vendor $vendor
     * @return void
     */
    public function saving(Vendor $vendor)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($vendor), VUD_01, 'VUD_01', 409);
    }

    /**
     * Handle the Vendor "deleting" event.
     *
     * @param Vendor $vendor
     * @return void
     */
    public function deleting(Vendor $vendor)
    {
        if (app()->runningInConsole()) {
            return;
        }

        cache()->tags('vendors')->flush();
    }

    private function exists(Vendor $vendor)
    {
        return $vendor
            ->query()
            ->where('id', '!=', $vendor->id)
            ->where(function ($query) use ($vendor) {
                $query->where('name', $vendor->name)
                    ->orWhere('short_code', $vendor->short_code);
            })
            ->exists();
    }
}
