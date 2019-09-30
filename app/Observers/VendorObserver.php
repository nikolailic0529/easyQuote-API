<?php namespace App\Observers;

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
        if($this->exists($vendor)) {
            throw new \ErrorException(__('vendor.exists_exception'));
        }
    }

    /**
     * Handle the Vendor "updating" event.
     *
     * @param Vendor $vendor
     * @return void
     */
    public function updating(Vendor $vendor)
    {
        if($vendor->isSystem()) {
            throw new \ErrorException(__('vendor.system_updating_exception'));
        }
    }

    /**
     * Handle the Vendor "deleting" event.
     *
     * @param Vendor $vendor
     * @return void
     */
    public function deleting(Vendor $vendor)
    {
        if($vendor->isSystem()) {
            throw new \ErrorException(__('vendor.system_deleting_exception'));
        }
    }

    private function exists(Vendor $vendor)
    {
        return $vendor
            ->where('id', '!=', $vendor->id)
            ->where(function ($query) {
                $query->where('user_id', request()->user()->id)
                    ->orWhere('is_system', true);
            })
            ->where(function ($query) use ($vendor) {
                $query->where('name', $vendor->name)
                    ->orWhere('short_code', $vendor->short_code);
            })
            ->exists();
    }
}
