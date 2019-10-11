<?php namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use League\Csv \ {
    Reader,
    Statement
};
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
     * Csv Statement Instance
     *
     * @var \League\Csv\Statement
     */
    protected $stmt;

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
     * Temporary Mapping Table Name
     *
     * @var string
     */
    protected $mappingTable;

    public function __construct(QuoteFile $quoteFile, $headerOffset = 0)
    {
        $this->quoteFile = $quoteFile;

        if(isset($quoteFile->dataSelectSeparator->separator)) {
            $this->delimiter = $quoteFile->dataSelectSeparator->separator;
        }

        $this->headerOffset = $headerOffset;

        $this->importableColumn = app(ImportableColumnRepository::class);

        $this->statement = app(Statement::class);

        $this->csv = Reader::createFromPath(Storage::path($quoteFile->original_file_path))
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset($this->headerOffset);

        $this->enclosure = $this->csv->getEnclosure();

        $this->header = $this->filterHeader($this->csv->getHeader());

        $this->dataTable = uniqid();

        $this->mappingTable = uniqid();
    }

    public function import()
    {
        $this->beforeImport();

        $user_id = $this->quoteFile->user_id;
        $quote_file_id = $this->quoteFile->id;

        // Inserting Rows
        DB::insert("
            insert into `imported_rows` (id, created_at, updated_at, processed_at, quote_file_id, user_id, page)
            select imported_row_id id, now() created_at, now() updated_at, now() processed_at, '{$quote_file_id}' quote_file_id, '{$user_id}' user_id, 1 page
            from `{$this->dataTable}_r`
        ");

        // Inserting Columns by Each Header
        foreach ($this->headersMapping as $column) {
            ['header' => $header, 'importable_column_id' => $importable_column_id] = $column;

            DB::insert("
                insert into `imported_columns` (id, imported_row_id, importable_column_id, value, header)
                select uuid() id, imported_row_id, '{$importable_column_id}', `{$header}` value, '{$header}'
                from `{$this->dataTable}_r`
            ");
        }
    }

    public function beforeImport()
    {
        try {
            $this->setRowsCount();
        } catch (\Exception $exception) {
            $this->quoteFile->setException($exception->getMessage());
            $this->quoteFile->markAsUnHandled();
            throw new \ErrorException($exception->getMessage());
        }

        $this->checkHeader();
        $this->loadIntoTempTable();
        $this->mapHeaders();
        $this->setHeadersCount();
    }

    private function checkHeader()
    {
        if(mb_strlen($this->header[0]) > 100) {
            $this->quoteFile->setException(__('parser.separator_exception'));
            throw new \ErrorException(__('parser.separator_exception'));
        }
    }

    private function filterHeader(array $header)
    {
        return collect($header)->map(function ($name, $key) {
            if(mb_strlen(trim($name)) === 0) {
                return "Unknown Header {$key}";
            }
            return str_replace('.', '_', $name);
        })->toArray();
    }

    private function loadIntoTempTable()
    {
        Schema::create($this->dataTable, function (Blueprint $table) {
            foreach ($this->header as $column) {
                $table->text($column);
            }
            $table->temporary();
        });

        $path = str_replace('\\', '/', Storage::path($this->quoteFile->original_file_path));

        $query = "
            load data local infile '{$path}'
            into table `{$this->dataTable}`
            fields terminated by '{$this->delimiter}'
            optionally enclosed by '{$this->enclosure}'
            ignore 1 lines
        ";

        DB::connection()->getpdo()->exec($query);

        /**
         * Assign Unique Ids to Rows
         */
        DB::select("
            create temporary table `{$this->dataTable}_r`
            select *, uuid() imported_row_id from `{$this->dataTable}`
        ");
    }

    private function setHeadersCount()
    {
        $this->headersCount = count($this->header);
    }

    private function setRowsCount()
    {
        $this->rowsCount = count($this->csv);
        $this->quoteFile->setRowsCount($this->rowsCount);
    }

    private function mapHeaders()
    {
        $aliasesMapping = $this->importableColumn->allSystem()->pluck('aliases.*.alias', 'id');
        $this->headersMapping = [];
        $mapping = collect([]);

        $this->headersMapping = collect($this->header)->map(function ($header) use ($aliasesMapping, $mapping) {
            $importable_column_id = $aliasesMapping->search(function ($aliases) use ($header) {
                $matchingHeader = preg_quote($header, '~');
                $match = preg_grep("~^{$matchingHeader}.*?~i", $aliases);
                if(empty($match)) {
                    return false;
                }
                return true;
            });

            if(!$importable_column_id) {
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

        Schema::create($this->mappingTable, function (Blueprint $table) {
            $table->text('header');
            $table->uuid('importable_column_id');
            $table->temporary();
        });

        DB::table($this->mappingTable)->insert($this->headersMapping);
    }
}
