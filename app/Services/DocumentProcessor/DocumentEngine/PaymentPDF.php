<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParsePaymentPDF;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\UpdatesDistributorFileData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Throwable;

class PaymentPDF implements ProcessesQuoteFile
{
    use UpdatesDistributorFileData;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(QuoteFile $quoteFile)
    {
        $response = (new ParsePaymentPDF($this->logger))
            ->filePath(Storage::path($quoteFile->original_file_path))
            ->page($quoteFile->imported_page)
            ->process();

        $data = $this->mapPaymentResponse($response);

        if (!empty($data)) {
            $this->updatePaymentQuoteFileData($quoteFile, $data);
        }
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
