<?php

namespace App\Domain\DocumentProcessing\DocumentEngine;

use App\Domain\DocumentEngine\ParserClientFactory;
use App\Domain\DocumentProcessing\Concerns\HasFallbackProcessor;
use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Domain\DocumentProcessing\Exceptions\NoDataFoundException;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DePdfRescuePaymentScheduleProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    public function __construct(protected LoggerInterface $logger,
                                protected ParserClientFactory $parserClientFactory,
                                private ProcessesQuoteFile $fallBackProcessor)
    {
    }

    /**
     * @throws \App\Foundation\Filesystem\Exceptions\FileException
     * @throws \Throwable
     * @throws \App\Domain\DocumentProcessing\Exceptions\NoDataFoundException
     */
    public function process(QuoteFile $quoteFile): void
    {
        $response = $this->parserClientFactory->buildGenericPdfPaymentScheduleParser()
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->page($quoteFile->imported_page)
            ->process();

        $data = $this->mapPaymentResponse($response);

        if (empty($data)) {
            throw NoDataFoundException::noDataFoundInFile($quoteFile->original_file_name);
        }

        $this->updatePaymentQuoteFileData($quoteFile, $data);
    }

    public function getFallbackProcessor(): ProcessesQuoteFile
    {
        return $this->fallBackProcessor;
    }

    protected function updatePaymentQuoteFileData(QuoteFile $quoteFile, array $value)
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            if ($quoteFile->scheduleData()->exists()) {
                $quoteFile->scheduleData()->forceDelete();
            }

            $scheduleData = $quoteFile->scheduleData()->make(['value' => $value]);
            $scheduleData->save();

            $quoteFile->markAsHandled();

            DB::commit();

            return $scheduleData;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function mapPaymentResponse(?array $response)
    {
        if (is_null($response)) {
            return [];
        }

        $payments = [];

        foreach ($response as $payment) {
            if (!Arr::has($payment, ['from_date', 'to_date', 'value'])) {
                customlog(['error' => "Unprocessable data. Expected keys 'from_date', 'to_date', 'value'."], $payment ?? []);

                continue;
            }

            $payments[] = [
                'from' => $payment['from_date'],
                'to' => $payment['to_date'],
                'price' => $payment['value'],
            ];
        }

        return $payments;
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('31b77d6a-7321-42d3-9ba0-0ba1ca9e4c0e');
    }
}
