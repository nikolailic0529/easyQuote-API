<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteEntity;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteVersionEntity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Queue\SerializesModels;

final class WorldwideQuoteVersionDeleted implements WithWorldwideQuoteEntity, WithWorldwideQuoteVersionEntity
{
    use SerializesModels;

    private WorldwideQuote $quote;

    private WorldwideQuoteVersion $quoteVersion;

    private ?User $actingUser;

    /**
     * Create a new event instance.
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

    public function getActingUser(): ?User
    {
        return $this->actingUser;
    }
}
