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
    Sheet, Row,
    Concerns\ToModel,
    Concerns\WithHeadingRow,
    Concerns\WithCustomCsvSettings,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Concerns\OnEachRow,
    Events\AfterSheet,
    Imports\HeadingRowFormatter
};
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Uuid, Str, Log;

class ImportedRowImport implements OnEachRow, WithHeadingRow, WithCustomCsvSettings, WithEvents, WithChunkReading
{
    use Importable;

    protected $quoteFile;

    protected $user;

    protected $importableColumns;

    protected $sheet;

    protected $activeSheetIndex;

    public function __construct(
        QuoteFile $quoteFile,
        User $user,
        Collection $importableColumns
    ) {
        $this->quoteFile = $quoteFile;
        $this->user = $user;
        $this->importableColumns = $importableColumns;
        $this->activeSheetIndex = 1;

        HeadingRowFormatter::default('none');
    }

    public function onRow(Row $row)
    {
        $columnsData = $this->fetchRow($row);

        if($this->isEmptyRow($columnsData)) {
            return null;
        };

        return $this->makeRow($columnsData);
    }

    public function getCsvSettings(): array
    {
        if(!$this->quoteFile->isCsv() && !$this->quoteFile->isWord()) {
            return [];
        }

        return [
            'delimiter' => $this->quoteFile->dataSelectSeparator->separator
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function () {
                $this->activeSheetIndex++;
            },
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function checkHeader(string $header)
    {
        if(Str::length($header) > 40) {
            throw new \ErrorException(__('parser.separator_exception'));
        }
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

    private function isEmptyRow(Collection $columnsData)
    {
        return $columnsData->filter(function ($columnData) {
            return !is_null($columnData->value);
        })->isEmpty() || $columnsData->isEmpty();
    }

    private function fetchRow(Row $row)
    {
        $row = $row->toCollection()->filter(function ($value, $key) {
            return gettype($key) === 'string';
        });

        $columns = $this->findColumns($row);
        $columnsData = $this->handleColumns($columns);

        return $columnsData->filter(function ($columnData) {
            return !is_null($columnData);
        });
    }

    private function findColumns(Collection $row)
    {
        $columns = $row->map(function ($value, $header) {
            $foundColumn = null;

            foreach ($this->importableColumns as $importableColumn) {
                if(!$this->findColumn($header, $importableColumn)) {
                    continue;
                };

                $foundColumn = $importableColumn;
                break;
            }

            $this->checkHeader($header);

            return compact('foundColumn', 'header', 'value');
        });

        return $columns;
    }

    private function findColumn(string $header, ImportableColumn $importableColumn)
    {
        $aliases = collect($importableColumn->aliases->toArray());
        $foundColumn = false;

        foreach ($aliases as $alias) {
            $match = preg_match("/^{$alias['alias']}.*?/i", $header);

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
        $columns = $columns->map(function ($column) {
            ['header' => $header, 'value' => $value, 'foundColumn' => $foundColumn] = $column;

            $columnData = $this->quoteFile->columnsData()->make([
                'value' => $value,
                'page' => $this->activeSheetIndex,
                'header' => $header
            ]);

            $columnData->user()->associate($this->user);

            if(isset($foundColumn)) {
                $columnData->importableColumn()->associate($foundColumn);
            }

            return $columnData;
        });

        return $columns;
    }
}
