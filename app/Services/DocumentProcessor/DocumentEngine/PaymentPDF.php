<?php

namespace App\Services\DocumentProcessor\DocumentEngine;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentEngine\ParsePaymentPDF;
use App\Services\DocumentProcessor\DocumentEngine\Concerns\UpdatesDistributorFileData;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Throwable;

class PaymentPDF implements ProcessesQuoteFile
{
    use UpdatesDistributorFileData;

    protected LoggerInterface $logger;

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected FilesystemManager $filesystem;

    public function __construct(LoggerInterface $logger, ConnectionInterface $connection, LockProvider $lockProvider, FilesystemManager $filesystem)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->filesystem = $filesystem;
    }

    public function process(QuoteFile $quoteFile)
    {
        $response = (new ParsePaymentPDF($this->logger))
            ->filePath($this->filesystem->disk()->path($quoteFile->original_file_path))
            ->page($quoteFile->imported_page)
            ->process();

        $data = $this->mapPaymentResponse($response);

        if (!empty($data)) {
            $this->updatePaymentQuoteFileData($quoteFile, $data);
        }
    }

    protected function updatePaymentQuoteFileData(QuoteFile $quoteFile, array $value): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            if ($quoteFile->scheduleData()->exists()) {
                $quoteFile->scheduleData()->forceDelete();
            }

            $scheduleData = $quoteFile->scheduleData()->make(['value' => $value]);
            $scheduleData->save();

            $quoteFile->handled_at = now();
            $quoteFile->save();

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

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
                $this->logger->error("Unprocessable data. Expected keys 'from_date', 'to_date', 'value'.", $payment ?? []);

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
