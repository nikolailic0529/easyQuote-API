<?php

namespace App\Events\RescueQuote;

use App\Models\Quote\QuoteNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RescueQuoteNoteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(protected QuoteNote $quoteNote)
    {
    }

    public function getQuoteNote(): QuoteNote
    {
        return $this->quoteNote;
    }
}
