<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;
use Closure;

class HasException extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if ($quoteFile->hasException()) {
            return true;
        }
    }
}
