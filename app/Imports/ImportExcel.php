<?php namespace App\Imports;

use App\Jobs\CreateRow;
use App\Models \ {
    User,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile
};
use App\Models\QuoteFile\ImportedColumn;
use Illuminate\Support\Collection;
use Maatwebsite\Excel \ {
    Row,
    Concerns\WithHeadingRow,
    Concerns\WithCustomCsvSettings,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Concerns\OnEachRow,
    Events\BeforeSheet,
    Events\BeforeImport,
    Events\AfterImport,
    Imports\HeadingRowFormatter,
    Imports\HeadingRowExtractor,
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Str;

class ImportExcel implements OnEachRow, WithHeadingRow, WithCustomCsvSettings, WithEvents, WithChunkReading
{
    use Importable;

    protected $quoteFile;

    protected $user;

    protected $importableColumns;

    protected $reader;

    protected $activeSheetIndex = 0;

    protected $headingRow = 1;

    protected $startRow = 2;

    protected $requiredHeaders = [
        'price', 'product_no'
    ];

    protected $headersMapping;

    protected $requiredHeadersMapping;

    protected $rowsCount = 0;

    public function __construct(
        QuoteFile $quoteFile,
        User $user,
        Collection $importableColumns
    ) {
        $this->quoteFile = $quoteFile;
        $this->user = $user;
        $this->importableColumns = $importableColumns;

        HeadingRowFormatter::default('none');
    }

    public function onRow(Row $row)
    {
        if($row->getIndex() < $this->startRow) {
            return null;
        }

        $row = $row->toCollection();
        $columnsData = $this->fetchRow($row);

        if(!$this->checkColumnsData($columnsData)) {
            return null;
        };

        $this->makeRow($columnsData)->save();

        $this->increaseRowsCount();
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->quoteFile->dataSelectSeparator->separator
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function ($event) {
                $this->reader = $event->reader;
            },
            BeforeSheet::class => function ($event) {
                $this->activeSheetIndex++;
                $this->headingRow = 1;
                $this->startRow = 2;

                $worksheet = $event->sheet->getDelegate();

                if($this->quoteFile->isExcel()) {
                    $highestRow = $worksheet->getHighestRow();

                    while(!$this->checkHeadingRow($worksheet) && $this->headingRow < $highestRow) {
                        continue;
                    }

                    $this->determineCoveragePeriod($worksheet);
                } else {
                    $this->mapHeaders($worksheet);
                }

                $this->mapRequiredHeaders();
            },
            AfterImport::class => function ($event) {
                if($this->rowsCount < 1) {
                    $this->quoteFile->setException(__('parser.no_rows_exception'));
                }
            }
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    private function increaseRowsCount()
    {
        $this->rowsCount++;
    }

    private function checkHeadingRow(Worksheet $worksheet)
    {
        $this->mapHeaders($worksheet);
        $this->mapRequiredHeaders();

        if(!$this->requiredHeadersPresent()) {
            $this->startRow++;
            $this->headingRow++;
            return false;
        }

        return true;
    }

    private function checkColumnsData(Collection $columnsData)
    {
        $pass = $this->requiredHeadersMapping->reject(function ($header, $id) use ($columnsData) {
            return $columnsData->contains(function ($column) use ($header) {
                return trim($column->header) === trim($header) && isset($column->value);
            });
        })->isEmpty();

        return $pass;
    }

    private function determineCoveragePeriod(Worksheet $worksheet)
    {
        $headingRow = HeadingRowExtractor::extract($worksheet, $this);

        $coverageExists = !empty(preg_grep('/^Coverage Period$/i', $headingRow));

        if(!$coverageExists) {
            return;
        }

        $nextHeadingRow = head(iterator_to_array($worksheet->getRowIterator($this->headingRow, $this->headingRow)));
        $cellIterator = $nextHeadingRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);

        foreach ($cellIterator as $cell) {
            if(preg_match('/Coverage Period/i', $cell->getValue())) {
                $cell->setValue('Coverage Period From');
                $columnIndex = $cellIterator->getCurrentColumnIndex();

                $worksheet->setCellValueByColumnAndRow(++$columnIndex, $this->headingRow, 'Coverage Period To');
                break;
            }
        }

        $this->mapHeaders($worksheet);
    }

    private function requiredHeadersPresent()
    {
        return $this->requiredHeadersMapping->reject(function ($value, $key) {
            return $value;
        })->isEmpty();
    }

    private function checkAndLimitHeader(string $header)
    {
        if($this->quoteFile->isCsv()) {
            if(Str::length($header) > 100) {
                $this->quoteFile->setException(__('parser.separator_exception'));
                $this->quoteFile->markAsUnHandled();
                throw new \ErrorException(__('parser.separator_exception'));
            }
        }

        return trim(Str::limit($header, 40, ''));
    }

    private function makeRow(Collection $columnsData)
    {
        $user_id = $this->user->id;
        $quote_file_id = $this->quoteFile->id;
        $page = $this->activeSheetIndex;

        $importedRow = ImportedRow::make(compact('user_id', 'quote_file_id', 'page'));
        $importedRow->columnsDataToCreate = $columnsData;

        return $importedRow;
    }

    private function fetchRow(Collection $row)
    {
        $columns = $row->map(function ($value, $header) {
            $importable_column_id = $this->headersMapping->get($header);
            $header = $this->checkAndLimitHeader($header);

            return ImportedColumn::make(compact('importable_column_id', 'value', 'header'));
        })->filter(function ($column) {
            return isset($column->importable_column_id) && $column->header !== $column->value;
        })->values();

        return $columns;
    }

    private function mapHeaders(Worksheet $worksheet)
    {
        $headingRow = collect(HeadingRowExtractor::extract($worksheet, $this))
            ->reject(function ($header) {
                return gettype($header) !== 'string';
            });

        $aliasesMapping = $this->importableColumns->pluck('aliases.*.alias', 'id');

        $this->headersMapping = [];
        $mapping = collect([]);
        $this->headersMapping = $headingRow->mapWithKeys(function ($header) use ($aliasesMapping, $mapping) {
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

                $importableColumn = $this->user->importableColumns()
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

    private function mapRequiredHeaders()
    {
        $this->requiredHeadersMapping = collect($this->requiredHeaders)->mapWithKeys(function ($name) {
            $aliases = $this->importableColumns->where('name', $name)->first()->aliases->pluck('alias');

            $header = $this->headersMapping->search(function ($id, $header) use ($aliases) {
                return $aliases->contains(function ($alias) use ($header) {
                    return preg_match("~^{$alias}.*?~i", $header);
                });
            });

            return [$name => $header];
        });
    }
}
