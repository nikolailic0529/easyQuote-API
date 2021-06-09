<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Imports\ImportExcelSchedule;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EqExcelRescuePaymentScheduleProcessor implements ProcessesQuoteFile
{
    public function process(QuoteFile $quoteFile)
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
}
