<?php

namespace App\Traits\Vendor;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasVendors
{
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
