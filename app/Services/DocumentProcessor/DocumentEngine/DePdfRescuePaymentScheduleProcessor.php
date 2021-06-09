<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParsePaymentPDF;
use App\Services\DocumentProcessor\Concerns\HasFallbackProcessor;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\DocumentEngineProcessor;
use App\Services\DocumentProcessor\Exceptions\NoDataFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Throwable;

class DePdfRescuePaymentScheduleProcessor implements ProcessesQuoteFile, DocumentEngineProcessor, HasFallbackProcessor
{
    protected LoggerInterface $logger;
    private ProcessesQuoteFile $fallBackProcessor;

    public function __construct(LoggerInterface $logger, ProcessesQuoteFile $fallBackProcessor)
    {
        $this->logger = $logger;
        $this->fallBackProcessor = $fallBackProcessor;
    }

    public function process(QuoteFile $quoteFile)
    {
        $response = (new ParsePaymentPDF($this->logger))
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
        } catch (Throwable $e) {
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
}
