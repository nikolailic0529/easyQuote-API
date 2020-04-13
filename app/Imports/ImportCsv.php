<?php

namespace App\Imports;

use App\Imports\Concerns\{
    MapsHeaders,
    LimitsHeaders
};
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use League\Csv\Reader;
use DB, Storage, Str;
use Throwable;

class ImportCsv
{
    use MapsHeaders, LimitsHeaders;

    protected Reader $csv;

    protected QuoteFile $quoteFile;

    protected string $delimiter = ',';

    protected int $headerOffset;

    protected array $header = [];

    protected ?string $importableFilePath;

    public function __construct(QuoteFile $quoteFile, $filePath = null, int $headerOffset = 0)
    {
        $this->quoteFile = $quoteFile;

        if (isset($quoteFile->dataSelectSeparator->separator)) {
            $this->delimiter = $quoteFile->dataSelectSeparator->separator;
        }

        $this->headerOffset = $headerOffset;

        $this->setImportableFilePath($quoteFile, $filePath);

        $this->csv = Reader::createFromPath($this->importableFilePath)
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset($this->headerOffset);

        $this->header = $this->filterHeader($this->csv->getHeader());
    }

    public function import(): void
    {
        $this->beforeImport();

        $this->csv->setHeaderOffset(null)
            ->setOutputBOM(Reader::BOM_UTF8)
            ->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8');

        $rows = $this->csv->getRecords();

        $importedRows = collect();

        DB::transaction(function () use ($rows, &$importedRows) {
            foreach ($rows as $offset => $row) {
                if ($offset === 0) {
                    continue;
                }

                $row = array_combine($this->header, $row);

                $importedRows->push($this->makeRow($row));

                if ($importedRows->count() > 100) {
                    ImportedRow::insert($importedRows->splice(0, 100)->toArray());
                }
            }

            ImportedRow::insert($importedRows->toArray());
        });

        $this->afterImport();
    }

    protected function beforeImport(): void
    {
        $this->checkHeader();
        $this->mapHeaders();
    }

    protected function afterImport(): void
    {
        if ($this->quoteFile->rowsCount === 0) {
            $this->quoteFile->setException(QFNRF_01, 'QFNRF_01');
            $this->quoteFile->throwExceptionIfExists();
            return;
        }
    }

    protected function checkHeader(): void
    {
        $this->limitHeader($this->quoteFile, $this->header[0]);
    }

    protected function filterHeader(array $header): array
    {
        $header = collect($header)->map(fn ($name, $key) => blank($name) ? 'Unknown Header ' . ++$key : $name);

        $header->duplicates()->each(fn ($name, $key) => $header->put($key, $name . ' ' . $key));

        return $header->toArray();
    }

    protected function setImportableFilePath(QuoteFile $quoteFile, $filePath): void
    {
        $path = isset($filePath) ? $filePath : $quoteFile->original_file_path;

        if (isset($quoteFile->fullPath) && $quoteFile->fullPath) {
            unset($quoteFile->fullPath);
        } else {
            $path = Storage::path($path);
        }

        $this->importableFilePath = $path;
    }

    protected function makeRow(array $row): array
    {
        $importedRow = [
            'id'            => (string) Str::uuid(),
            'quote_file_id' => $this->quoteFile->id,
            'user_id'       => $this->quoteFile->user_id,
            'columns_data'  => [],
            'page'          => 1,
            'is_one_pay'    => (bool) data_get($row, '_one_pay', false),
            'created_at'    => (string) now()
        ];

        try {
            $columns_data = json_encode($this->mapColumnsData($row), JSON_THROW_ON_ERROR);

            return compact('columns_data') + $importedRow;
        } catch (Throwable $e) {
            logger(implode(' - ', [IMPR_ERR_01, $e->getMessage()]), $row);

            return $importedRow;
        }
    }

    protected function mapColumnsData(array $row): array
    {
        unset($row['_one_pay']);

        return collect($row)->map(
            fn ($value, $header) =>
            ['header' => $header, 'value' => $value, 'importable_column_id' => $this->headersMapping->get($header)]
        )
            ->keyBy('importable_column_id')
            ->toArray();
    }
}
