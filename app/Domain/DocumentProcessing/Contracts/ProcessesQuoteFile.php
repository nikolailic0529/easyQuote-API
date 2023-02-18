<?php

namespace App\Domain\DocumentProcessing\Contracts;

use App\Domain\QuoteFile\Models\QuoteFile;
use Ramsey\Uuid\UuidInterface;

interface ProcessesQuoteFile
{
    /**
     * @throws \App\Domain\DocumentProcessing\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void;

    public static function getProcessorUuid(): UuidInterface;
}
