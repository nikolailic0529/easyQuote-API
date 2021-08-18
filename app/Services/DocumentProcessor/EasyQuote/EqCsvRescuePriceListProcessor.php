<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Imports\ImportCsv;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

class EqCsvRescuePriceListProcessor implements ProcessesQuoteFile
{
    public function __construct(protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider)
    {
    }

    public function process(QuoteFile $quoteFile): void
    {
        if (request()->has('data_select_separator_id')) {
            $quoteFile->dataSelectSeparator()->associate(request()->data_select_separator_id)->save();
        }

        $quoteFile->imported_page = 1;
        $quoteFile->handled_at = now();

        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30, function () use ($quoteFile) {
            $this->connection->transaction(fn () => $quoteFile->rowsData()->forceDelete());

            (new ImportCsv($quoteFile, Storage::path($quoteFile->original_file_path)))->import();

            $this->connection->transaction(fn () => $quoteFile->save());
        });
    }

    public static function getProcessorUuid(): UuidInterface
    {
        return Uuid::fromString('b1940067-46ea-44cd-aa65-82a9e29d3068');
    }
}
