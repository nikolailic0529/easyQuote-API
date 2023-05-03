<?php

namespace App\Domain\Rescue\Services\HandledCases;

use App\Domain\QuoteFile\Models\QuoteFile;

class RequestedNewPageForSchedule extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if ($quoteFile->isSchedule() && $quoteFile->isNewPage(request()->page)) {
            return true;
        }
    }
}
