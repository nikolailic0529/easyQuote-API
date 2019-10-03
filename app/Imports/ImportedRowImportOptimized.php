<?php namespace App\Imports;

use App\Models \ {
    User,
    QuoteFile\ImportedRow,
    QuoteFile\ImportedColumn,
    QuoteFile\ImportableColumn,
    QuoteFile\QuoteFile
};
use Illuminate\Support\Collection;
use Maatwebsite\Excel \ {
    Row,
    Concerns\WithHeadingRow,
    Concerns\WithStartRow,
    Concerns\WithCustomCsvSettings,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Concerns\ToModel,
    Concerns\OnEachRow,
    Events\AfterSheet,
    Events\BeforeSheet,
    Events\BeforeImport,
    Imports\HeadingRowFormatter,
    Imports\HeadingRowExtractor,
};
use PhpOffice\PhpSpreadsheet\Worksheet \ {
    Worksheet,
    RowCellIterator
};
use Uuid, Str, Log;

class ImportedRowImportOptimized implements ToModel, WithStartRow, WithHeadingRow, WithCustomCsvSettings, WithEvents, WithChunkReading
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
        'price', 'qty'
    ];

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

    public function model(array $row)
    {
        $row = collect($row);
        $columnsData = $this->fetchRow($row);

        if(!isset($columnsData)) {
            return null;
        };

        return $this->makeRow($columnsData);
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->quoteFile->dataSelectSeparator->separator ?? null
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
                $highestRow = $worksheet->getHighestRow();
                $hasHeading = false;

                if(!$this->quoteFile->isExcel()) {
                    return;
                }

                while(!$hasHeading && $this->headingRow < $highestRow) {
                    $hasHeading = $this->checkHeadingRow($worksheet);
                }

                $this->determineCoveragePeriod($worksheet);
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

    public function startRow(): int
    {
        return $this->startRow;
    }

    private function checkHeadingRow(Worksheet $worksheet)
    {
        $headingRow = HeadingRowExtractor::extract($worksheet, $this);
        $headingRow = $this->filterHeadingRow(collect($headingRow));

        $foundColumns = $this->findColumns($headingRow, true)->filter(function ($found) {
            return !is_null($found['foundColumn']);
        });

        if($foundColumns->isEmpty() || $this->hasNotRequiredHeaders($foundColumns)) {
            $this->startRow++;
            $this->headingRow++;
            return false;
        }

        return true;
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
    }

    private function hasNotRequiredHeaders(Collection $foundColumns)
    {
        $requiredHeaders = collect($this->requiredHeaders)->implode('|');
        $foundHeaders = $foundColumns->pluck('foundColumn.name')->map(function ($name) use ($requiredHeaders) {
            if(preg_match("/{$requiredHeaders}/i", $name, $match)) {
                return $match[0];
            };
        });

        return $foundHeaders->unique()->count() < count($this->requiredHeaders);
    }

    private function filterHeadingRow(Collection $row)
    {
        return $row->filter(function ($value) {
            return isset($value);
        });
    }

    private function filterRowKeys(array $row)
    {
        return collect($row)->filter(function ($value, $key) {
            return gettype($key) === 'string';
        });
    }

    private function checkAndLimitHeader(string $header)
    {
        if($this->quoteFile->isCsv()) {
            if(Str::length($header) > 100) {
                throw new \ErrorException(__('parser.separator_exception'));
            }
        }

        return Str::limit($header, 40, '');
    }

    private function makeRow(Collection $columnsData)
    {
        $importedRow = new ImportedRow;
        $importedRow->id = (string) Uuid::generate(4);
        $importedRow->page = $this->activeSheetIndex;

        $importedRow->user()->associate($this->user);
        $importedRow->quoteFile()->associate($this->quoteFile);
        $importedRow->save();

        $importedRow->columnsData()->saveMany($columnsData);

        return $importedRow;
    }

    private function isBlankRow(Collection $row) {
        return $row->filter(function ($value) {
            return isset($value);
        })->isEmpty();
    }

    private function fetchRow(Collection $row)
    {
        if($this->isBlankRow($row)) {
            return null;
        }

        $row = $this->filterRowKeys($row->toArray());
        $columns = $this->findColumns($row);
        $columnsData = $this->handleColumns($columns);

        if(!$this->requiredExistsInColumns($columnsData)) {
            return null;
        };

        return $columnsData;
    }

    private function requiredExistsInColumns(Collection $columnsData)
    {
        $requiredHeaders = collect($this->requiredHeaders)->implode('^|');

        $foundHeaders = $columnsData->map(function ($column) use ($requiredHeaders) {
            if(isset($column->value) && mb_strlen($column->value) > 0 && preg_match("/{$requiredHeaders}/", $column->importableColumn->name, $match)) {
                return $match[0];
            };
        });

        return $foundHeaders->unique()->count() >= count($this->requiredHeaders);
    }

    private function findColumns(Collection $row, bool $isHeading = false)
    {
        $columns = $row->map(function ($value, $header) use ($isHeading) {
            $headingString = $isHeading ? $value : $header;
            $foundColumn = null;

            foreach ($this->importableColumns as $importableColumn) {
                if(!$this->findColumn($headingString, $importableColumn)) {
                    continue;
                };

                $foundColumn = $importableColumn;
                break;
            }

            $header = $this->checkAndLimitHeader($headingString);

            return compact('foundColumn', 'header', 'value');
        });

        return $columns;
    }

    private function findColumn(string $header, ImportableColumn $importableColumn)
    {
        $aliases = collect($importableColumn->aliases->toArray());
        $foundColumn = false;

        foreach ($aliases as $alias) {
            $match = preg_match("~^{$alias['alias']}.*?~i", $header);

            if(!$match) {
                continue;
            };

            $foundColumn = $importableColumn;
            break;
        };

        return $foundColumn;
    }

    private function handleColumns(Collection $columns)
    {
        $columns = $columns->reduce(function ($carry, $column) {
            ['header' => $header, 'value' => $value, 'foundColumn' => $foundColumn] = $column;

            $columnData = $this->quoteFile->columnsData()->make([]);

            $columnData->user()->associate($this->user);

            $columnData->fill([
                'page' => $this->activeSheetIndex,
                'header' => trim($header),
                'value' => $value
            ]);

            $importableColumn = $columnData->associateImportableColumnOrCreate($foundColumn, $carry);

            $this->importableColumns->push($importableColumn);
            $this->importableColumns = $this->importableColumns->unique('id');

            $carry->push($columnData);

            return $carry;
        }, collect());

        return $columns;
    }
}
