<?php

namespace App\Traits;

use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuote
{
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withDefault();
    }
}
