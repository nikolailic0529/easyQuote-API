<?php

namespace App\Domain\Vendor\Concerns;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasVendors
{
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
