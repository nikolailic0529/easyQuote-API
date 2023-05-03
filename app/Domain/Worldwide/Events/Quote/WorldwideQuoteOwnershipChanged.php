<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Database\Eloquent\Model;

final class WorldwideQuoteOwnershipChanged
{
    public function __construct(
        public readonly WorldwideQuote $quote,
        public readonly WorldwideQuote $oldQuote,
        public readonly ?Model $causer = null,
    ) {
    }
}
