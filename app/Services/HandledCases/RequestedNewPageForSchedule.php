<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;

class RequestedNewPageForSchedule extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule() && $quoteFile->isNewPage(request()->page)) {
            return true;
        }
    }
}
