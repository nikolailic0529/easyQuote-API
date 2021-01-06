<?php

namespace App\Traits\QuoteTemplate;

use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuoteTemplates
{
    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(QuoteTemplate::class);
    }
}
