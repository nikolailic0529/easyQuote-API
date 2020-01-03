<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;

class HasNotBeenProcessed extends HandledCase
{
    public function applyCase(QuoteFile $quoteFile)
    {
        if (!$quoteFile->isHandled()) {
            return true;
        }
    }
}
