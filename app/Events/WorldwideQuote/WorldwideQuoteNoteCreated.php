<?php

namespace App\Events\WorldwideQuote;

use App\Models\Quote\WorldwideQuoteNote;
use App\Models\User;

final class WorldwideQuoteNoteCreated
{
    private WorldwideQuoteNote $worldwideQuoteNote;
    private ?User $actingUser;

    public function __construct(WorldwideQuoteNote $worldwideQuoteNote,
                                ?User $actingUser = null)
    {
        $this->worldwideQuoteNote = $worldwideQuoteNote;
        $this->actingUser = $actingUser;
    }

    public function getWorldwideQuoteNote(): WorldwideQuoteNote
    {
        return $this->worldwideQuoteNote;
    }

    /**
     * @return \App\Models\User|null
     */
    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
