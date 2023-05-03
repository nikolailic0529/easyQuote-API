<?php

namespace App\Domain\Worldwide\Events\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;

final class WorldwideQuoteExported
{
    public function __construct(protected WorldwideQuote $quote)
    {
    }
}
