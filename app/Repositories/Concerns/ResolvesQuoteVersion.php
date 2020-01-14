<?php

namespace App\Repositories\Concerns;

use App\Models\Quote\{
    BaseQuote,
    Quote,
    QuoteVersion
};

trait ResolvesQuoteVersion
{
    protected function resolveQuoteVersion(Quote $quote, QuoteVersion $version): BaseQuote
    {
        if ($quote->is($version)) {
            return $quote;
        }

        return $version;
    }
}
