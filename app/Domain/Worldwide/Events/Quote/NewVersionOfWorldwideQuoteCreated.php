<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVersionOfWorldwideQuoteCreated
{
    use Dispatchable;
    use SerializesModels;

    private WorldwideQuoteVersion $previousQuoteVersion;
    private WorldwideQuoteVersion $newQuoteVersion;
    private ?User $actingUser;

    /**
     * Create a new event instance.
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

    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
