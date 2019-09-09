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
    Sheet,
    Concerns\ToModel,
    Concerns\WithHeadingRow,
    Concerns\WithCustomCsvSettings,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Events\AfterSheet
};
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Uuid, Log;

class ImportedRowImport implements ToModel, WithHeadingRow, WithCustomCsvSettings, WithEvents, WithChunkReading
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
    }

    public function model(array $row)
    {
        $row = collect($row);

        $columnsData = $this->fetchRow($row);

        if($this->isEmptyRow($columnsData)) {
            return null;
        };
        
        return $this->makeRow($columnsData);
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
            AfterSheet::class => function () {
                $this->activeSheetIndex++;
            },
        ];
    }
    
    public function chunkSize(): int
    {
        return 1000;
    }

    protected function makeRow(Collection $columnsData)
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

    protected function isEmptyRow(Collection $columnsData)
    {
        return $columnsData->filter(function ($columnData) {
            return !is_null($columnData->value);
        })->isEmpty() || $columnsData->isEmpty();
    }

    protected function fetchRow(Collection $row)
    {
        $columnsData = $this->importableColumns->map(function ($importableColumn, $key) use ($row) {
            $alias = $this->findAlias($row, $importableColumn);

            if(is_null($alias)) {
                return;
            }

            $columnData = $importableColumn->columnsData()->make([
                'value' => $row->get($alias),
                'page' => $this->activeSheetIndex
            ]);

            $columnData->user()->associate($this->user);
            $columnData->quoteFile()->associate($this->quoteFile);

            return $columnData;
        });

        return $columnsData->filter(function ($columnData) {
            return !is_null($columnData);
        });
    }

    protected function findAlias(Collection $row, ImportableColumn $importableColumn)
    {
        $aliases = collect($importableColumn->aliases->toArray())
            ->filter(function ($alias) use ($row) {
                return $row->has(data_get($alias, 'alias'));
            });

        if($aliases->isEmpty()) {
            return null;
        }

        return data_get($aliases->first(), 'alias');
    }
}
