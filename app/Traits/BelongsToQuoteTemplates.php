<?php

namespace App\Traits;

use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToQuoteTemplates
{
    public function quoteTemplates(): BelongsToMany
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }
}
