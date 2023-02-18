<?php

namespace App\Domain\Rescue\Services\HandledCases;

use App\Domain\QuoteFile\Models\QuoteFile;

class HasNotBeenProcessed extends HandledCase
{
    public function applyCase(QuoteFile $quoteFile)
    {
        if (!$quoteFile->isHandled()) {
            return true;
        }
    }
}
