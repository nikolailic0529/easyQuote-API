<?php

namespace App\Events\WorldwideQuote;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorldwidePackQuoteAssetsReviewStepProcessed implements WithWorldwideQuoteEntity
{
    use Dispatchable, SerializesModels;

    private WorldwideQuote $quote;
    private WorldwideQuote $oldQuote;
    private ?User $actingUser;

    /**
     * Create a new event instance.
     *
     * @param WorldwideQuote $quote
     * @param WorldwideQuote $oldQuote
     * @param \App\Models\User|null $actingUser
     */
    public function __construct(WorldwideQuote $quote,
                                WorldwideQuote $oldQuote,
                                ?User $actingUser = null)
    {
        $this->quote = $quote;
        $this->oldQuote = $oldQuote;
        $this->actingUser = $actingUser;
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

    /**
     * @return \App\Models\User|null
     */
    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
