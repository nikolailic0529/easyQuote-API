<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Imports\ImportCsv;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EqCsvRescuePriceListProcessor implements ProcessesQuoteFile
{
    public function process(QuoteFile $quoteFile)
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        if (request()->has('data_select_separator_id')) {
            $quoteFile->dataSelectSeparator()->associate(request()->data_select_separator_id)->save();
        }

        DB::beginTransaction();

        try {
            $quoteFile->rowsData()->forceDelete();

            (new ImportCsv($quoteFile, Storage::path($quoteFile->original_file_path)))->import();

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
