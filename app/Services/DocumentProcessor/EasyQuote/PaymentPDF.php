<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentPDF implements ProcessesQuoteFile
{
    protected PdfParserInterface $parser;

    protected FilesystemManager $filesystem;

    public function __construct(PdfParserInterface $parser, FilesystemManager $filesystem)
    {
        $this->parser = $parser;
        $this->filesystem = $filesystem;
    }

    public function process(QuoteFile $quoteFile)
    {
        $textData = $this->parser->getText($this->filesystem->disk()->path($quoteFile->original_file_path));

        $pagesData = array_filter($textData, function (array $pageData) use ($quoteFile) {
            return $pageData['page'] >= $quoteFile->imported_page;
        });

        $parsedData = array_reduce($pagesData, function (array $parsedData, array $pageData) {

            $pageResult = $this->parser->parseSchedule($pageData);

            return array_merge($parsedData, $pageResult);

        }, []);

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
