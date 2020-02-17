<?php

namespace App\Traits;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVendor
{
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withDefault();
    }
}
