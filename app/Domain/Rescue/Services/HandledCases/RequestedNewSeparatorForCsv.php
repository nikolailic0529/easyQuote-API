<?php

namespace App\Domain\Rescue\Services\HandledCases;

use App\Domain\QuoteFile\Models\QuoteFile;

class RequestedNewSeparatorForCsv extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if (!$quoteFile->isPrice()) {
            return false;
        }

        if ($quoteFile->isCsv() && $quoteFile->isNewSeparator(request()->data_select_separator_id)) {
            return true;
        }
    }
}
