<?php namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use League\Csv \ {
    Reader,
    Statement,
    ResultSet
};
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use App\Jobs\CreateBulkColumnsData;
use App\Jobs\CreateBulkRows;
use App\Models\QuoteFile\ImportedColumn;
use App\Models\QuoteFile\ImportedRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Str;
use Webpatser\Uuid\Uuid;

class ImportCsvBackup
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
    protected $limit = 500;

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

        $this->header = $this->csv->getHeader();
    }

    public function import()
    {
        $this->beforeImport();

        $stmt = $this->statement(true);

        while ($this->offset < $this->rowsCount) {
            $rowsChunk = $this->fetchRowsChunk($stmt->process($this->csv));
            $this->createRowsChunk($rowsChunk);

            $stmt = $this->statement();
        }

    }

    public function statement(bool $initial = false)
    {
        if($initial) {
            return $this->statement->offset($this->offset)->limit($this->limit);
        }

        $this->offset += $this->limit;

        if($this->offset >= $this->rowsCount) {
            return false;
        }

        if($this->offset + $this->limit > $this->rowsCount) {
            $this->limit = $this->rowsCount - $this->offset;
        }

        return $this->statement->offset($this->offset)->limit($this->limit);
    }

    public function beforeImport()
    {
        try {
            $this->setRowsCount();
        } catch (\Exception $exception) {
            $this->quoteFile->setException($exception->getMessage());
            throw new \ErrorException($exception->getMessage());
        }

        $this->mapHeaders();
        $this->setHeadersCount();
    }

    public function createRowsChunk(array $rowsChunk)
    {
        CreateBulkRows::withChain(
            $this->createColumnsDataChunks($rowsChunk['imported_columns'])
        )->dispatch($rowsChunk['imported_rows'])->onQueue('import_rows');
    }

    private function setHeadersCount()
    {
        $this->headersCount = count($this->header);
    }

    private function createColumnsDataChunks(array $imported_columns)
    {
        return collect($imported_columns)->chunk(50 * $this->headersCount)->map(function ($chunk) {
            return (new CreateBulkColumnsData($chunk->toArray()))->onQueue('import_columns');
        })->toArray();
    }

    private function fetchRowsChunk(ResultSet $resultSet)
    {
        $imported_rows = [];
        $imported_columns = [];
        foreach (iterator_to_array($resultSet, true) as $row) {
            $row = $this->makeRow($row);
            $imported_rows[] = $row['imported_row'];
            array_push($imported_columns, ...$row['columns_data']);
        }

        return compact('imported_rows', 'imported_columns');
    }

    private function setRowsCount()
    {
        $this->rowsCount = count($this->csv);
        $this->quoteFile->setRowsCount($this->rowsCount);
    }

    private function makeRow(array $row)
    {
        $id = (string) Uuid::generate(4);
        $user_id = $this->quoteFile->user->id;
        $quote_file_id = $this->quoteFile->id;
        $page = 1;
        $columns_data = $this->fetchRow($row, $id);

        $imported_row = compact(
            'id',
            'user_id',
            'quote_file_id',
            'page'
        );

        return compact('imported_row', 'columns_data');
    }

    private function checkHeader(string $header)
    {
        if(Str::length($header) < 100) {
            return true;
        }

        $this->quoteFile->setException(__('parser.separator_exception'));
        throw new \ErrorException(__('parser.separator_exception'));
    }

    private function fetchRow(array $row, string $imported_row_id)
    {
        $columns = collect($row)->map(function ($value, $header) use ($imported_row_id) {
            $this->checkHeader($header);

            $id = (string) Uuid::generate(4);
            $header = trim(Str::limit($header, 40, ''));
            $importable_column_id = $this->headersMapping->get($header);

            return compact(
                'id',
                'imported_row_id',
                'importable_column_id',
                'value',
                'header'
            );
        })->values()->toArray();

        return $columns;
    }

    private function mapHeaders()
    {
        $aliasesMapping = $this->importableColumn->allSystem()->pluck('aliases.*.alias', 'id');
        $this->headersMapping = [];
        $mapping = collect([]);

        $this->headersMapping = collect($this->header)->mapWithKeys(function ($header) use ($aliasesMapping, $mapping) {
            $column = $aliasesMapping->search(function ($aliases) use ($header) {
                $matchingHeader = preg_quote($header, '~');
                $match = preg_grep("~^{$matchingHeader}.*?~i", $aliases);
                if(empty($match)) {
                    return false;
                }
                return true;
            });

            if(!$column) {
                $alias = $header;
                $name = Str::columnName($header);

                $importableColumn = $this->quoteFile->user->importableColumns()
                    ->where('name', $name)
                    ->whereNotIn('id', $mapping->toArray())
                    ->firstOrCreate(compact('header', 'name'));
                $importableColumn->aliases()->where('alias', $name)->firstOrCreate(compact('alias'));
                $column = $importableColumn->id;
            }

            $mapping->push($column);

            return [$header => $column];
        });
    }
}
