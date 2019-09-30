<?php namespace App\Traits\Vendor;

use App\Models\Vendor;

trait HasVendors
{
    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
