<?php

namespace App\Events\WorldwideQuote;

use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVersionOfWorldwideQuoteCreated
{
    use Dispatchable, SerializesModels;

    private WorldwideQuoteVersion $previousQuoteVersion;
    private WorldwideQuoteVersion $newQuoteVersion;
    private ?User $actingUser;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Quote\WorldwideQuoteVersion $previousQuoteVersion
     * @param \App\Models\Quote\WorldwideQuoteVersion $newQuoteVersion
     * @param \App\Models\User|null $actingUser
     */
    public function __construct(WorldwideQuoteVersion $previousQuoteVersion,
                                WorldwideQuoteVersion $newQuoteVersion,
                                ?User $actingUser = null)
    {
        $this->previousQuoteVersion = $previousQuoteVersion;
        $this->newQuoteVersion = $newQuoteVersion;
        $this->actingUser = $actingUser;
    }

    public function getPreviousQuoteVersion(): WorldwideQuoteVersion
    {
        return $this->previousQuoteVersion;
    }

    public function getNewQuoteVersion(): WorldwideQuoteVersion
    {
        return $this->newQuoteVersion;
    }

    /**
     * @return \App\Models\User|null
     */
    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
