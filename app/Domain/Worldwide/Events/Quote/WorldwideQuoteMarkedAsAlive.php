<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteEntity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorldwideQuoteMarkedAsAlive implements WithWorldwideQuoteEntity
{
    use Dispatchable;
    use SerializesModels;

    private WorldwideQuote $quote;
    private ?User $actingUser;

    /**
     * Create a new event instance.
     */
    public function __construct(WorldwideQuote $quote, ?User $actingUser = null)
    {
        $this->quote = $quote;
        $this->actingUser = $actingUser;
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }

    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
