<?php

namespace App\Events;

use App\Models\Quote\QuoteNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteNoteCreated
{
    use Dispatchable, SerializesModels;

    public QuoteNote $quoteNote;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(QuoteNote $quoteNote)
    {
        $this->quoteNote = $quoteNote;
    }
}
