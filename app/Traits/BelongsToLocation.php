<?php

namespace App\Traits;

use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToLocation
{
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }
}