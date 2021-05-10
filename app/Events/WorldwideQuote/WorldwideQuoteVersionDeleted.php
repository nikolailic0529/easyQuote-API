<?php

namespace App\Events\WorldwideQuote;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Contracts\WithWorldwideQuoteVersionEntity;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

final class WorldwideQuoteVersionDeleted implements WithWorldwideQuoteEntity, WithWorldwideQuoteVersionEntity
{
    use SerializesModels;

    private WorldwideQuote $quote;

    private WorldwideQuoteVersion $quoteVersion;

    private ?User $actingUser;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Quote\WorldwideQuote $quote
     * @param \App\Models\Quote\WorldwideQuoteVersion $version
     * @param \App\Models\User|null $actingUser
     */
    public function __construct(WorldwideQuote $quote, WorldwideQuoteVersion $version, ?User $actingUser = null)
    {
        $this->quote = $quote;
        $this->quoteVersion = $version;
        $this->actingUser = $actingUser;
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->quote;
    }

    public function getQuoteVersion(): WorldwideQuoteVersion
    {
        return $this->quoteVersion;
    }

    /**
     * @return \App\Models\User|null
     */
    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
