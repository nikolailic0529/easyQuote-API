<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAddress
{
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withDefault();
    }
}