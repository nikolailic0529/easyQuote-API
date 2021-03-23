<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Services\DocumentEngine\ParseDistributorPDF;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParseDistributorWorldwidePDF;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class WorldwideDistributorPDF implements ProcessesQuoteFile
{
    protected LoggerInterface $logger;

    protected DistributorFileDataMapper $dataMapper;

    public function __construct(LoggerInterface $logger, DistributorFileDataMapper $dataMapper)
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
