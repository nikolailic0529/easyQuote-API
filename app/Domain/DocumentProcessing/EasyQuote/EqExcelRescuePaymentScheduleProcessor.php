<?php

namespace App\Domain\DocumentProcessing\EasyQuote;

use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\EasyQuote\Parsers\ExcelPaymentScheduleParser;
use App\Domain\DocumentProcessing\EasyQuote\Parsers\Exceptions\PaymentScheduleParserException;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EqExcelRescuePaymentScheduleProcessor implements ProcessesQuoteFile
{
    public function __construct(
        protected ConnectionResolverInterface $connectionResolver,
        protected LockProvider $lockProvider,
        protected ExcelPaymentScheduleParser $paymentScheduleParser,
    ) {
    }

    public function process(QuoteFile $quoteFile): void
    {
        try {
            $data = $this->paymentScheduleParser->parse(
                new \SplFileInfo(Storage::path($quoteFile->original_file_path)),
                $quoteFile->imported_page,
            );
        } catch (PaymentScheduleParserException $e) {
            return;
        }

        $scheduleData = tap($quoteFile->scheduleData()->getRelated()->newInstance(),
            static function (ScheduleData $model) use ($data, $quoteFile): void {
                $model->quoteFile()->associate($quoteFile);
                $model->value = $data->toArray();
            });

        $quoteFile->handled_at = now();

        $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10)
            ->block(30, function () use ($quoteFile, $scheduleData) {
                $this->connectionResolver->connection()
                    ->transaction(static function () use ($scheduleData, $quoteFile): void {
                        $quoteFile->scheduleData()->forceDelete();
                        $quoteFile->save();

                        $scheduleData->save();
                    });
            });
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('94102e0c-8cb4-4b0a-9dc4-4a78af90c624');
    }
}
