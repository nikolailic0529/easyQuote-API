<?php

namespace App\Events\WorldwideQuote;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorldwidePackQuoteMarginStepProcessed implements WithWorldwideQuoteEntity
{
    use Dispatchable, SerializesModels;

    private WorldwideQuote $quote;

    private WorldwideQuote $oldQuote;

    /**
     * Create a new event instance.
     *
     * @param WorldwideQuote $quote
     * @param WorldwideQuote $oldQuote
     */
    public function __construct(WorldwideQuote $quote, WorldwideQuote $oldQuote)
    {
        $this->quote = $quote;
        $this->oldQuote = $oldQuote;
    }

    /**
     * @return WorldwideQuote
     */
    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }

    /**
     * @return WorldwideQuote
     */
    public function getOldQuote(): WorldwideQuote
    {
        return $this->oldQuote;
    }
}
