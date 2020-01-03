<?php

namespace App\Imports\Concerns;

use App\Models\QuoteFile\QuoteFile;
use Str;

trait LimitsHeaders
{
    protected function limitHeader(QuoteFile $quoteFile, string $header): string
    {
        if ($quoteFile->isCsv()) {
            if (Str::length($header) > 100) {
                $quoteFile->setException(QFWS_01, 'QFWS_01');
                $quoteFile->markAsUnHandled();
                $quoteFile->throwExceptionIfExists();
            }
        }

        return trim(mb_substr(trim($header), 0, 40));
    }
}
