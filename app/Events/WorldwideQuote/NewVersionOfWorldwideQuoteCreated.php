<?php

namespace App\Events\WorldwideQuote;

use App\Models\Quote\WorldwideQuoteVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVersionOfWorldwideQuoteCreated
{
    use Dispatchable, SerializesModels;

    private WorldwideQuoteVersion $previousQuoteVersion;
    private WorldwideQuoteVersion $newQuoteVersion;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $previousQuoteVersion
     * @param \App\Models\Quote\WorldwideQuoteVersion $newQuoteVersion
     * @return void
     */
    public function __construct(WorldwideQuoteVersion $previousQuoteVersion, WorldwideQuoteVersion $newQuoteVersion)
    {
        $this->previousQuoteVersion = $previousQuoteVersion;
        $this->newQuoteVersion = $newQuoteVersion;
    }

    public function getPreviousQuoteVersion(): WorldwideQuoteVersion
    {
        return $this->previousQuoteVersion;
    }

    public function getNewQuoteVersion(): WorldwideQuoteVersion
    {
        return $this->newQuoteVersion;
    }
}
