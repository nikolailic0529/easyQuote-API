<?php

namespace App\Events\WorldwideQuote;

use App\Models\Quote\WorldwideQuote;

final class WorldwideQuoteExported
{
    public function __construct(protected WorldwideQuote $quote)
    {
    }
}