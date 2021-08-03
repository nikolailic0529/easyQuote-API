<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Imports\ImportExcelSchedule;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

class EqExcelRescuePaymentScheduleProcessor implements ProcessesQuoteFile
{
    public function process(QuoteFile $quoteFile): void
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        DB::beginTransaction();

        try {
            $quoteFile->scheduleData()->forceDelete();

            (new ImportExcelSchedule($quoteFile))->import(Storage::path($quoteFile->original_file_path));

            $quoteFile->markAsHandled();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('94102e0c-8cb4-4b0a-9dc4-4a78af90c624');
    }
}
