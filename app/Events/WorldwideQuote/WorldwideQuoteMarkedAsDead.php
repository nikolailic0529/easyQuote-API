<?php

namespace App\Events\WorldwideQuote;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorldwideQuoteMarkedAsDead implements WithWorldwideQuoteEntity
{
    use Dispatchable, SerializesModels;

    private WorldwideQuote $quote;
    private ?User $actingUser;

    /**
     * Create a new event instance.
     *
     * @param WorldwideQuote $quote
     * @param \App\Models\User|null $actingUser
     */
    public function __construct(WorldwideQuote $quote,
                                ?User $actingUser = null)
    {
        $this->quote = $quote;
        $this->actingUser = $actingUser;
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }

    /**
     * @return \App\Models\User|null
     */
    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
