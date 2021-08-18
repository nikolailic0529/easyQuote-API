<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\RescueWordPriceListParser;
use App\Services\DocumentEngine\ParserClientFactory;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Services\DocumentProcessor\Exceptions\NoDataFoundException;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DeWordRescuePriceListProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{

    public function __construct(protected LoggerInterface $logger,
                                protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider,
                                protected PriceListResponseDataMapper $dataMapper,
                                protected ParserClientFactory $parserClientFactory,
                                private ProcessesQuoteFile $fallbackProcessor)
    {
    }

    /**
     * @throws \App\Services\Exceptions\FileException
     * @throws \Throwable
     * @throws \App\Services\DocumentProcessor\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $quoteFile->imported_page = 1;
        $quoteFile->save();

        $lock->block(30, function () use ($quoteFile) {

            $this->connection->transaction(fn () => $quoteFile->save());

        });

        $data = $this->parserClientFactory->buildRescueWordPriceListParser()
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
        return Uuid::fromString('f476e7f3-0345-4d3a-8c09-fea421dd8edc');
    }
}
