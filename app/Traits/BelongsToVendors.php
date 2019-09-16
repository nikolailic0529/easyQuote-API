<?php namespace App\Traits;

use App\Models\Vendor;

trait BelongsToVendors
{
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class);
    }
}
