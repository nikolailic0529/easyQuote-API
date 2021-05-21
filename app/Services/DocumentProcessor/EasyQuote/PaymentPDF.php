<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;

class PaymentPDF implements ProcessesQuoteFile
{
    protected ConnectionInterface $connection;
    protected LockProvider $lockProvider;
    protected PdfParserInterface $parser;

    public function __construct(ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                PdfParserInterface $parser)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile)
    {
        $rawData = $this->parser->getText(Storage::path($quoteFile->original_file_path));

        $pages = array_values(array_filter($rawData, function (array $pageData) use ($quoteFile) {
            return $pageData['page'] >= $quoteFile->imported_page;
        }));

        $paymentData = [];

        foreach ($pages as $pageData) {
            $paymentDataFromPage = $this->parser->parseSchedule($pageData);

            $paymentData = array_merge($paymentData, $paymentDataFromPage);
        }

        $this->updateScheduleQuoteFileData($quoteFile, $paymentData);
    }

    protected function updateScheduleQuoteFileData(QuoteFile $quoteFile, array $data): void
    {
        /** @var ScheduleData $scheduleData */
        $scheduleData = tap(new ScheduleData(), function (ScheduleData $scheduleData) use ($quoteFile, $data) {
            $scheduleData->{$scheduleData->getKeyName()} = (string)Uuid::generate(4);
            $scheduleData->quoteFile()->associate($quoteFile);
            $scheduleData->value = $data;
        });

        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30, function () use ($quoteFile, $scheduleData) {

            $this->connection->transaction(function () use ($quoteFile, $scheduleData) {

                $quoteFile->scheduleData()->forceDelete();

                $scheduleData->save();

                $quoteFile->markAsHandled();

            });

        });
    }
}
