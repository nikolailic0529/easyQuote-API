<?php

namespace App\Traits\QuoteTemplate;

use App\Models\QuoteTemplate\QuoteTemplate;

trait HasQuoteTemplates
{
    public function quoteTemplates()
    {
        return $this->hasMany(QuoteTemplate::class);
    }
}
