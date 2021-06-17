<?php

namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use Ramsey\Uuid\UuidInterface;

interface ProcessesQuoteFile
{
    /**
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return mixed
     * @throws \App\Services\DocumentProcessor\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile);

    public static function getProcessorUuid(): UuidInterface;
}