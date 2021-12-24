<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParserClientFactory;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Services\DocumentProcessor\Exceptions\NoDataFoundException;
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
     * @inheritDoc
     * @throws \App\Services\Exceptions\FileException
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