<?php namespace App\Traits;

use App\Models\Vendor;

trait BelongsToVendor
{
    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->withDefault(Vendor::make([]));
    }
}
