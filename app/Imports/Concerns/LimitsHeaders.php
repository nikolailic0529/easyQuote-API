<?php

namespace App\Imports\Concerns;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Str;
use ValueError;

trait LimitsHeaders
{
    protected function limitHeader(QuoteFile $quoteFile, string $header): string
    {
        if ($quoteFile->isCsv()) {
            if (Str::length($header) > 100) {
                throw new ValueError('An invalid data delimiter selected');
            }
        }

        return trim(mb_substr(trim($header), 0, 40));
    }
}
