<?php

namespace App\Services\HandledCases;

use App\Models\QuoteFile\QuoteFile;

class RequestedNewPageForPrice extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if ($quoteFile->isPrice() && $quoteFile->isNewPage(request()->page)) {
            $quoteFile->setImportedPage(request()->page);
        }
    }
}
