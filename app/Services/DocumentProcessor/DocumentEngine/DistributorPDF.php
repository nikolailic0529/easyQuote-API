<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Services\DocumentEngine\ParseDistributorPDF;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\UpdatesDistributorFileData;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class DistributorPDF implements ProcessesQuoteFile
{
    use UpdatesDistributorFileData;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(QuoteFile $quoteFile)
    {
        $data = (new ParseDistributorPDF($this->logger))
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $data = $this->mapDistributorResponse($quoteFile, $data);

        if (!empty($data)) {
            $this->updateDistributorQuoteFileData($quoteFile, $data);
        }
    }
}
