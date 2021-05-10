<?php

namespace App\Services\DocumentProcessor\EasyQuote;

use App\Contracts\Services\ProcessesQuoteFile;
use App\Enum\Lock;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentReaders\ExcelPriceListReader;
use App\Services\DocumentReaders\Models\Row;
use Generator;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;

class DistributorExcel implements ProcessesQuoteFile
{
    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    public function __construct(ConnectionInterface $connection, LockProvider $lockProvider)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
    }

    /**
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws \Throwable
     */
    public function process(QuoteFile $quoteFile)
    {
        $rows = (new ExcelPriceListReader())->readFile(Storage::path($quoteFile->original_file_path));

        $rowModels = value(function () use ($quoteFile, $rows): array {
            $models = [];

            foreach ($rows as $row) {
                $models[] = $this->mapRowToModel($row, $quoteFile);
            }

            return $models;
        });

        $rowBatch = array_map(fn(ImportedRow $row) => $row->getAttributes(), $rowModels);

        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10);

        $lock->block(30, function () use ($rowBatch, $quoteFile) {

            $this->connection->transaction(function () use ($rowBatch, $quoteFile) {

                $quoteFile->rowsData()->forceDelete();

                // TODO: implement chunk insert
                ImportedRow::query()->insert($rowBatch);

            });


        });
    }

    protected function mapRowToModel(Row $row, QuoteFile $quoteFile): ImportedRow
    {
        return tap(new ImportedRow(), function (ImportedRow $importedRow) use ($row, $quoteFile) {
            $importedRow->{$importedRow->getKeyName()} = (string)Uuid::generate(4);
            $importedRow->quoteFile()->associate($quoteFile);
            $importedRow->page = $row->getHeadingRow()->getSheetIndex() + 1;
            $importedRow->columns_data = array_map(function ($value, string $columnKey) use ($row) {

                return [
                    'importable_column_id' => $columnKey,
                    'value' => $value,
                    'header' => $row->getHeadingRow()->getMapping()[$columnKey] ?? \Str::random(20),
                ];

            }, $row->getRowValues(), array_keys($row->getRowValues()));
            $importedRow->is_one_pay = value(function () use ($row): bool {

                foreach ($row->getRowValues() as $value) {
                    if (preg_match('/return to/i', $value)) {
                        return true;
                    }
                }

                return false;

            });
            $importedRow->{$importedRow->getCreatedAtColumn()} = $importedRow->freshTimestampString();
            $importedRow->{$importedRow->getUpdatedAtColumn()} = $importedRow->freshTimestampString();
        });
    }
}
