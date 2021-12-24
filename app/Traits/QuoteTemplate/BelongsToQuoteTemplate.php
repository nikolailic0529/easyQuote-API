<?php

namespace App\Traits\QuoteTemplate;

use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuoteTemplate
{
    public function quoteTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class)->withTrashed()->withDefault();
    }
}
