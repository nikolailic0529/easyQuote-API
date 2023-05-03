<?php

namespace App\Domain\Template\Concerns;

use App\Domain\Rescue\Models\QuoteTemplate;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuoteTemplates
{
    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(QuoteTemplate::class);
    }
}
