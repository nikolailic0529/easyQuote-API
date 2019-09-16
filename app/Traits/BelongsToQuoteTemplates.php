<?php namespace App\Traits;

use App\Models\QuoteTemplate\QuoteTemplate;

trait BelongsToQuoteTemplates
{
    public function quoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }
}
