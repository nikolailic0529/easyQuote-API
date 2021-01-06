<?php

namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;

interface ProcessesQuoteFile
{
    public function process(QuoteFile $quoteFile);
}