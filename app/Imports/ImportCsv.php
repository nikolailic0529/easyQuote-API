<?php

namespace App\Imports;

use App\Imports\Concerns\{
    MapsHeaders,
    LimitsHeaders
};
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use League\Csv\Reader;
use DB, Storage;

class ImportCsv
{
    use MapsHeaders, LimitsHeaders;

    /**
     * Csv Reader Instance
     *
     * @var \League\Csv\Reader
     */
    protected $csv;

    /**
     * QuoteFile Model
     *
     * @var QuoteFile
     */
    protected $quoteFile;

    /**
     * Delimiter
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * Rows Count
     *
     * @var integer
     */
    protected $rowsCount;

    /**
     * Heading Row Offset
     *
     * @var integer
     */
    protected $headerOffset;

    /**
     * Header
     *
     * @var array
     */
    protected $header;

    /**
     * Current Offset for chunk
     *
     * @var integer
     */
    protected $offset = 0;

    /**
     * Limit for chunk
     *
     * @var integer
     */
    protected $limit = 1000;

    /**
     * Enclosure character
     *
     * @var string
     */
    protected $enclosure;

    /**
     * Temporary Data Table Name
     *
     * @var string
     */
    protected $dataTable;

    /**
     * PDO driver instance
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Importable File Path
     *
     * @var string
     */
    protected $importableFilePath;

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

        $this->enclosure = $this->csv->getEnclosure();

        $this->header = $this->filterHeader($this->csv->getHeader());

        $this->dataTable = uniqid();

        $this->pdo = DB::connection()->getPdo();
    }

    public function import(): void
    {
        $this->beforeImport();

        $user_id = $this->quoteFile->user_id;
        $quote_file_id = $this->quoteFile->id;

        $rows = DB::table($this->dataTable)->get();

        DB::transaction(
            fn () =>
            $rows->each(function ($row) use ($user_id, $quote_file_id) {
                ImportedRow::create([
                    'quote_file_id' => $quote_file_id,
                    'user_id'       => $user_id,
                    'columns_data'  => $this->mapColumnsData($row),
                    'page'          => 1,
                    'is_one_pay'    => (bool) data_get($row, '_one_pay', false)
                ]);
            })
        );


        $this->afterImport();
    }

    protected function beforeImport(): void
    {
        $this->checkHeader();
        $this->loadIntoTempTable();
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
        return collect($header)->map(function ($name, $key) {
            if (blank($name)) {
                return 'Unknown Header ' . ++$key;
            }
            return $name;
        })->toArray();
    }

    protected function loadIntoTempTable(): void
    {
        $columns = collect($this->header)->map(function ($column) {
            return "`{$column}` text";
        })->implode(',');

        $path = str_replace('\\', '/', $this->importableFilePath);

        $this->pdo->exec("
            create temporary table `{$this->dataTable}` ({$columns});
            load data local infile '{$path}'
            into table `{$this->dataTable}`
            fields terminated by '{$this->delimiter}'
            optionally enclosed by '{$this->enclosure}'
            ignore 1 lines;
        ");
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

    protected function mapColumnsData(object $row): array
    {
        unset($row->{'_one_pay'});

        return array_map(function ($value, $header) {
            return [
                'header'                => $header,
                'value'                 => $value,
                'importable_column_id'  => $this->headersMapping->get($header)
            ];
        }, (array) $row, array_keys((array) $row));
    }
}
