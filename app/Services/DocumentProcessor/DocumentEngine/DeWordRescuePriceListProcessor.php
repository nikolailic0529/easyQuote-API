<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParseDistributorWord;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class DeWordRescuePriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    protected LoggerInterface $logger;
    protected PriceListResponseDataMapper $dataMapper;
    private ProcessesQuoteFile $fallbackProcessor;

    public function __construct(LoggerInterface $logger,
                                PriceListResponseDataMapper $dataMapper,
                                ProcessesQuoteFile $fallbackProcessor)
    {
        $this->logger = $logger;
        $this->dataMapper = $dataMapper;
        $this->fallbackProcessor = $fallbackProcessor;
    }

    public function process(QuoteFile $quoteFile)
    {
        $data = (new ParseDistributorWord($this->logger))
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $data = $this->dataMapper->mapDistributorResponse($quoteFile, $data);

        if (!empty($data)) {
            $this->dataMapper->updateDistributorQuoteFileData($quoteFile, $data);
        }
    }

    public function getFallbackProcessor(): ProcessesQuoteFile
    {
        return $this->fallbackProcessor;
    }
}
