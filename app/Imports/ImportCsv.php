<?php

namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use League\Csv\Reader;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Str, DB, Storage;

class ImportCsv
{
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
     * Header Mapping header → importable_column_id
     *
     * @var array
     */
    protected $headersMapping;

    /**
     * Headers count
     *
     * @var integer
     */
    protected $headersCount;

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

        $this->importableColumn = app(ImportableColumnRepository::class);

        $this->setImportableFilePath($quoteFile, $filePath);

        $this->csv = Reader::createFromPath($this->importableFilePath)
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset($this->headerOffset);

        $this->enclosure = $this->csv->getEnclosure();

        $this->header = $this->filterHeader($this->csv->getHeader());

        $this->dataTable = uniqid();

        $this->pdo = DB::connection()->getPdo();
    }

    public function import()
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
        $colsQuery = collect($this->headersMapping)->map(function ($column) {
            ['header' => $header, 'importable_column_id' => $importable_column_id] = $column;

            return "
                insert into `imported_columns` (id, imported_row_id, importable_column_id, value, header)
                select uuid() id, imported_row_id, '{$importable_column_id}', `{$header}` value, '{$header}'
                from `{$this->dataTable}`
            ";
        })->implode(';');

        $this->pdo->exec($colsQuery);
    }

    public function beforeImport()
    {
        $this->checkHeader();
        $this->loadIntoTempTable();
        $this->mapHeaders();
        $this->setHeadersCount();
    }

    private function checkHeader()
    {
        if (mb_strlen($this->header[0]) > 100) {
            $this->quoteFile->setException(QFWS_01);
            throw new \ErrorException(QFWS_01);
        }
    }

    private function filterHeader(array $header)
    {
        return collect($header)->map(function ($name, $key) {
            if (mb_strlen(trim($name)) === 0) {
                return "Unknown Header {$key}";
            }
            return $name;
        })->toArray();
    }

    private function loadIntoTempTable()
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

    private function setHeadersCount()
    {
        $this->headersCount = count($this->header);
    }

    private function mapHeaders()
    {
        $aliasesMapping = $this->importableColumn->allSystem()->pluck('aliases.*.alias', 'id');
        $this->headersMapping = [];
        $mapping = collect([]);

        $this->headersMapping = collect($this->header)->map(function ($header, $key) use ($aliasesMapping, $mapping) {
            $column = false;
            $column_num = $key + 1;

            if (filled($header)) {
                $importable_column_id = $aliasesMapping->search(function ($aliases, $importable_column_id) use ($header, $mapping) {
                    if ($mapping->contains($importable_column_id)) {
                        return false;
                    }

                    $matchingHeader = preg_quote($header, '~');
                    $match = preg_grep("~^{$matchingHeader}.*?~i", $aliases);
                    if (empty($match)) {
                        return false;
                    }
                    return true;
                });
            }

            blank($header) && $header = "Unknown Header {$column_num}";

            if (!$importable_column_id) {
                $alias = $header;
                $name = Str::columnName($header);

                $importableColumn = $this->quoteFile->user->importableColumns()
                    ->where('name', $name)
                    ->whereNotIn('id', $mapping->toArray())
                    ->firstOrCreate(compact('header', 'name'));
                $importableColumn->aliases()->where('alias', $name)->firstOrCreate(compact('alias'));
                $importable_column_id = $importableColumn->id;
            }

            $mapping->push($importable_column_id);

            return compact('header', 'importable_column_id');
        })->toArray();
    }

    private function setImportableFilePath(QuoteFile $quoteFile, $filePath)
    {
        $path = isset($filePath) ? $filePath : $quoteFile->original_file_path;

        if (isset($quoteFile->fullPath) && $quoteFile->fullPath) {
            unset($quoteFile->fullPath);
        } else {
            $path = Storage::path($path);
        }

        $this->importableFilePath = $path;
    }
}
