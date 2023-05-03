<?php

namespace App\Domain\Rescue\Services\HandledCases;

use App\Domain\QuoteFile\Models\QuoteFile;

class RequestedNewPageForPrice extends HandledCase
{
    protected function applyCase(QuoteFile $quoteFile)
    {
        if ($quoteFile->isPrice() && $quoteFile->isNewPage(request()->page)) {
            $quoteFile->setImportedPage(request()->page);
        }
    }
}
