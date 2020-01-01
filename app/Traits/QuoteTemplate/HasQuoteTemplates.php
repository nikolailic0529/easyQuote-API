<?php

namespace App\Traits\QuoteTemplate;

use App\Models\QuoteTemplate\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuoteTemplates
{
    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(QuoteTemplate::class);
    }
}
