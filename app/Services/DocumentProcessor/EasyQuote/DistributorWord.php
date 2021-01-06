<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Contracts\Services\WordParserInterface;
use App\Enum\Lock;
use App\Facades\Setting;
use App\Imports\ImportCsv;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\QuoteFile\QuoteFile;
use App\Services\TemporaryDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DistributorWord implements ProcessesQuoteFile
{
    protected WordParserInterface $parser;

    public function __construct(WordParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile)
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        DB::beginTransaction();

        try {
            $separator = DataSelectSeparator::where('name', Setting::get('parser.default_separator'))->first();

            $quoteFile->dataSelectSeparator()->associate($separator)->save();

            $parsedData = $this->parser->parseAsDistributorFile(Storage::path($quoteFile->original_file_path));

            $filePath = $this->wordDistributorFileDataToFile($parsedData);

            (new ImportCsv($quoteFile, $filePath))->import();

            $quoteFile->markAsHandled();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function wordDistributorFileDataToFile(array $parsedData): string
    {
        $tempDir = (new TemporaryDirectory)->create();

        $filePath = $tempDir->path(Str::random(40) . '.csv');

        file_put_contents($filePath, head($parsedData)['content'] ?? '');

        return $filePath;
    }
}