<?php

namespace App\Domain\DocumentProcessing\DocumentEngine;

use App\Domain\DocumentEngine\ParserClientFactory;
use App\Domain\DocumentProcessing\Concerns\HasFallbackProcessor;
use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Domain\DocumentProcessing\Exceptions\NoDataFoundException;
use App\Domain\QuoteFile\Models\QuoteFile;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DePdfRescuePriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    public function __construct(protected LoggerInterface $logger,
                                protected PriceListResponseDataMapper $dataMapper,
                                protected ParserClientFactory $parserClientFactory,
                                private ProcessesQuoteFile $fallbackProcessor)
    {
    }

    /**
     * @throws \Throwable
     * @throws \App\Foundation\Filesystem\Exceptions\FileException
     * @throws \App\Domain\DocumentProcessing\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void
    {
        $data = $this->parserClientFactory->buildRescuePdfPriceListParser()
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $data = $this->dataMapper->mapDistributorResponse($quoteFile, $data);

        if (empty($data)) {
            throw NoDataFoundException::noDataFoundInFile($quoteFile->original_file_name);
        }

        $this->dataMapper->updateDistributorQuoteFileData($quoteFile, $data);
    }

    public function getFallbackProcessor(): ProcessesQuoteFile
    {
        return $this->fallbackProcessor;
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('256a550e-74a9-4ff1-a133-40bc645a13f5');
    }
}
