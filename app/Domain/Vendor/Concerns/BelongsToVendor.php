<?php

namespace App\Domain\Vendor\Concerns;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVendor
{
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withDefault();
    }
}
