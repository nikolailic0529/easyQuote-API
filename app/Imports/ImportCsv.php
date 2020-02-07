<?php

namespace App\Imports;

use App\Imports\Concerns\{
    MapsHeaders,
    LimitsHeaders
};
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

        // Inserting Rows
        $this->pdo->exec("
            insert into `imported_rows` (id, created_at, updated_at, processed_at, quote_file_id, user_id, page)
            select imported_row_id id, now() created_at, now() updated_at, now() processed_at, '{$quote_file_id}' quote_file_id, '{$user_id}' user_id, 1 page
            from `{$this->dataTable}`
        ");

        // Inserting Columns by Each Header
        $colsQuery = $this->headersMapping->map(function ($importable_column_id, $header) {
            return "
                insert into `imported_columns` (id, imported_row_id, importable_column_id, value, header)
                select uuid() id, imported_row_id, '{$importable_column_id}', `{$header}` value, '{$header}'
                from `{$this->dataTable}`
            ";
        })->implode(';');

        $this->pdo->exec($colsQuery);

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

        $this->findPriceAttributes();
    }

    protected function checkHeader(): void
    {
        $this->limitHeader($this->quoteFile, $this->header[0]);
    }

    protected function filterHeader(array $header): array
    {
        return collect($header)->map(function ($name, $key) {
            if (blank($name)) {
                return 'Unknown Header '. ++$key;
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

        /**
         * Assign Unique Ids to Rows
         */
        $this->pdo->exec("
            alter table `{$this->dataTable}` add `imported_row_id` char(36) first;
            update `{$this->dataTable}` set `imported_row_id` = uuid();
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

    protected function findPriceAttributes(): void
    {
        $attributes = [
            'pricing_document'      => $this->getPriceHeaderAttributes(ImportCsvOptions::REGEXP_PD),
            'system_handle'         => $this->getPriceHeaderAttributes(ImportCsvOptions::REGEXP_SH),
            'service_agreement_id'  => $this->getPriceHeaderAttributes(ImportCsvOptions::REGEXP_SAID)
        ];

        $this->quoteFile->storeMetaAttributes($attributes);
    }

    private function getPriceHeaderAttributes(string $regexp): array
    {
        if (!($header = head(preg_grep($regexp, $this->header)))) {
            return [];
        }

        return DB::table($this->dataTable)->select($header)->distinct($header)
            ->get()->pluck($header)->toArray();
    }
}
