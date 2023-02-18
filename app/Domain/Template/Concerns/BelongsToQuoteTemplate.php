<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Rescue\Models\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuoteTemplate
{
    public function quoteTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class)->withTrashed()->withDefault();
    }
}
