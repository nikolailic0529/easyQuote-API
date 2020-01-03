<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;

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
