<?php namespace App\Traits\QuoteTemplate;

use App\Models\QuoteTemplate\QuoteTemplate;

trait BelongsToQuoteTemplate
{
    public function quoteTemplate()
    {
        return $this->belongsTo(QuoteTemplate::class);
    }
}
