<?php


namespace App\Events\WorldwideQuote;


use App\Models\Quote\WorldwideQuoteNote;

final class WorldwideQuoteNoteCreated
{
    protected WorldwideQuoteNote $worldwideQuoteNote;

    public function __construct(WorldwideQuoteNote $worldwideQuoteNote)
    {
        $this->worldwideQuoteNote = $worldwideQuoteNote;
    }

    public function getWorldwideQuoteNote(): WorldwideQuoteNote
    {
        return $this->worldwideQuoteNote;
    }
}
