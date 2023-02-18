<?php

namespace App\Domain\DocumentProcessing\EasyQuote;

use App\Domain\DocumentProcessing\Contracts\ProcessesQuoteFile;
use App\Domain\DocumentProcessing\Contracts\WordParserInterface;
use App\Domain\QuoteFile\Imports\ImportCsv;
use App\Domain\QuoteFile\Models\DataSelectSeparator;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Settings\Facades\Setting;
use App\Domain\Sync\Enum\Lock;
use App\Foundation\Filesystem\TemporaryDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EqWordRescuePriceListProcessor implements ProcessesQuoteFile
{
    protected WordParserInterface $parser;

    public function __construct(WordParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function process(QuoteFile $quoteFile): void
    {
        $lock = Cache::lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);
        $lock->block(30);

        DB::beginTransaction();

        try {
            $separator = DataSelectSeparator::where('name', Setting::get('parser.default_separator'))->first();

            $quoteFile->dataSelectSeparator()->associate($separator);
            $quoteFile->imported_page = 1;
            $quoteFile->save();

            $parsedData = $this->parser->parseAsDistributorFile(Storage::path($quoteFile->original_file_path));

            $filePath = $this->wordDistributorFileDataToFile($parsedData);

            (new ImportCsv($quoteFile, $filePath))->import();

            $quoteFile->markAsHandled();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function wordDistributorFileDataToFile(array $parsedData): string
    {
        $tempDir = (new TemporaryDirectory())->create();

        $filePath = $tempDir->path(Str::random(40).'.csv');

        file_put_contents($filePath, head($parsedData)['content'] ?? '');

        return $filePath;
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('87ece929-0818-430f-be7b-93a1997299a9');
    }
}
