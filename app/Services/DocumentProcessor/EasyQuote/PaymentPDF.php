<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentPDF implements ProcessesQuoteFile
{
    protected PdfParserInterface $parser;

    public function __construct(PdfParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile)
    {
        // TODO: implement continuous pages parsing.

        $rawData = $this->parser->getText(Storage::path($quoteFile->original_file_path));

        $pageData = collect($rawData)->firstWhere('page', $quoteFile->imported_page);

        $parsedData = $this->parser->parseSchedule($pageData);

        $this->updateScheduleQuoteFileData($quoteFile, $parsedData);
    }

    protected function updateScheduleQuoteFileData(QuoteFile $quoteFile, array $data): void
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30);

        DB::beginTransaction();

        try {
            $quoteFile->scheduleData()->forceDelete();

            $scheduleData = ScheduleData::make(['value' => $data]);

            $scheduleData->quoteFile()->associate($quoteFile)->save();

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
