<?php

namespace App\Events\WorldwideQuote;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorldwideQuoteDeleted implements WithWorldwideQuoteEntity
{
    use Dispatchable, SerializesModels;

    private WorldwideQuote $quote;

    /**
     * Create a new event instance.
     *
     * @param WorldwideQuote $quote
     */
    public function __construct(WorldwideQuote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return WorldwideQuote
     */
    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }
}
