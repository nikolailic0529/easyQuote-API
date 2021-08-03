<?php

namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use Ramsey\Uuid\UuidInterface;

interface ProcessesQuoteFile
{
    /**
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return void
     * @throws \App\Services\DocumentProcessor\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void;

    public static function getProcessorUuid(): UuidInterface;
}