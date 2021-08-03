<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParseDistributorWorldwidePDF;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Services\DocumentProcessor\Exceptions\NoDataFoundException;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DePdfWorldwidePriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    protected LoggerInterface $logger;
    protected PriceListResponseDataMapper $dataMapper;
    protected ProcessesQuoteFile $fallbackProcessor;

    public function __construct(LoggerInterface $logger, PriceListResponseDataMapper $dataMapper, ProcessesQuoteFile $fallbackProcessor)
    {
        $this->logger = $logger;
        $this->dataMapper = $dataMapper;
        $this->fallbackProcessor = $fallbackProcessor;
    }

    /**
     * @throws \App\Services\Exceptions\FileException
     * @throws \Throwable
     * @throws \App\Services\DocumentProcessor\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void
    {
        $data = (new ParseDistributorWorldwidePDF($this->logger))
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $data = $this->dataMapper->mapDistributorResponse($quoteFile, $data);

        if (empty($data)) {
            throw NoDataFoundException::noDataFoundInFile($quoteFile->original_file_name);
        }

        $this->dataMapper->updateDistributorQuoteFileData($quoteFile, $data);
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('a74a4bb6-4451-4c25-8352-da9b17407972');
    }

    public function getFallbackProcessor(): ProcessesQuoteFile
    {
        return $this->fallbackProcessor;
    }
}
