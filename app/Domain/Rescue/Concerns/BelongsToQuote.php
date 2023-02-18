<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\Rescue\Models\Quote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuote
{
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withDefault();
    }
}
