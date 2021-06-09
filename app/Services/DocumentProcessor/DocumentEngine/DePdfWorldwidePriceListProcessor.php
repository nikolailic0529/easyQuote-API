<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParseDistributorWorldwidePDF;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class DePdfWorldwidePriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor
{
    protected LoggerInterface $logger;

    protected PriceListResponseDataMapper $dataMapper;

    public function __construct(LoggerInterface $logger, PriceListResponseDataMapper $dataMapper)
    {
        $this->logger = $logger;
        $this->dataMapper = $dataMapper;
    }

    public function process(QuoteFile $quoteFile)
    {
        $data = (new ParseDistributorWorldwidePDF($this->logger))
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $data = $this->dataMapper->mapDistributorResponse($quoteFile, $data);

        if (!empty($data)) {
            $this->dataMapper->updateDistributorQuoteFileData($quoteFile, $data);
        }
    }
}
