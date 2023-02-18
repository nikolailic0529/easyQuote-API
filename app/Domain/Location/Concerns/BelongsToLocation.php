<?php

namespace App\Domain\Location\Concerns;

use App\Domain\Location\Models\Location;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToLocation
{
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }
}
