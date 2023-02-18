<?php

namespace App\Domain\DocumentProcessing\DocumentEngine;

use App\Domain\DocumentEngine\ParserClientFactory;
use App\Domain\DocumentProcessing\Concerns\HasFallbackProcessor;
use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Domain\DocumentProcessing\Exceptions\NoDataFoundException;
use App\Domain\QuoteFile\Models\QuoteFile;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DeExcelPriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    public function __construct(protected LoggerInterface $logger,
                                protected FilesystemAdapter $filesystemAdapter,
                                protected ConnectionInterface $connection,
                                protected PriceListResponseDataMapper $responseDataMapper,
                                protected ParserClientFactory $parserClientFactory,
                                private ProcessesQuoteFile $fallbackProcessor)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws \App\Foundation\Filesystem\Exceptions\FileException
     */
    public function process(QuoteFile $quoteFile): void
    {
        $response = $this->parserClientFactory->buildGenericExcelPriceListParser()
            ->filePath($this->filesystemAdapter->path($quoteFile->original_file_path))
            ->firstPage($quoteFile->imported_page)
            ->process();

        $mappedResponse = $this->responseDataMapper->mapDistributorResponse(quoteFile: $quoteFile, response: $response);

        if (empty($mappedResponse)) {
            throw NoDataFoundException::noDataFoundInFile($quoteFile->original_file_name);
        }

        $this->responseDataMapper->updateDistributorQuoteFileData(quoteFile: $quoteFile, data: $mappedResponse);
    }

    public function getFallbackProcessor(): ProcessesQuoteFile
    {
        return $this->fallbackProcessor;
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('e7c335f2-5e43-4c38-ac3c-b8f286d8547f');
    }
}
