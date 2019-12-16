<?php

namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\{
    Row,
    Concerns\WithHeadingRow,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Concerns\OnEachRow,
    Events\BeforeSheet,
    Events\AfterImport,
    Imports\HeadingRowFormatter,
    Imports\HeadingRowExtractor,
};
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webpatser\Uuid\Uuid;
use Str, Arr, DB;

class ImportExcel implements OnEachRow, WithHeadingRow, WithEvents, WithChunkReading
{
    use Importable;

    protected $quoteFile;

    protected $user;

    protected $importableColumn;

    protected $systemImportableColumns;

    protected $activeSheetIndex = 0;

    protected $headingRow = 1;

    protected $startRow = 2;

    protected $requiredHeaders = [
        'price', 'product_no'
    ];

    protected $headersMapping;

    protected $headersCount = 0;

    protected $requiredHeadersMapping;

    protected $rowsCount = 0;

    protected $importableSheetData = [];

    protected $chunkSize = 500;

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile->load('user');
        $this->user = $quoteFile->user;
        $this->importableColumn = app(ImportableColumnRepository::class);
        $this->systemImportableColumns = $this->importableColumn->allSystem();

        HeadingRowFormatter::default('none');
    }

    public function onRow(Row $row)
    {
        if ($row->getIndex() < $this->startRow) {
            return null;
        }

        $row = $row->toCollection(null, true);
        $importableRow = $this->fetchRow($row);

        if (!$this->checkColumnsData($importableRow['imported_columns'])) {
            return null;
        };

        $this->makeRow($importableRow);

        $this->increaseRowsCount();
        $this->performChunkInsert();
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function ($event) {
                $this->beforeNextSheet();

                $worksheet = $event->sheet->getDelegate();
                $highestRow = $worksheet->getHighestRow();

                while (!$this->checkHeadingRow($worksheet) && $this->headingRow < $highestRow) {
                    continue;
                }

                $this->determineCoveragePeriod($worksheet);
            },
            AfterSheet::class => function ($event) {
                $this->importSheetData();
            },
            AfterImport::class => function ($event) {
                if ($this->rowsCount < 1) {
                    $this->quoteFile->setException(config('constants.QFNRF_01'));
                    $this->quoteFile->markAsUnHandled();
                }
            }
        ];
    }

    public function chunkSize(): int
    {
        return 10000;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    private function performChunkInsert()
    {
        if (count(Arr::pluck($this->importableSheetData, 'imported_columns'), true) < $this->chunkSize * $this->headersCount) {
            return;
        }

        $this->performInsert(array_splice($this->importableSheetData, 0, $this->chunkSize));
    }

    private function performInsert(array $data)
    {
        $importableRows = Arr::pluck($data, 'imported_row');
        $importableColumns = Arr::collapse(Arr::pluck($data, 'imported_columns.*'));

        DB::table('imported_rows')->insert($importableRows);
        DB::table('imported_columns')->insert($importableColumns);
    }

    private function importSheetData()
    {
        if (empty($this->importableSheetData)) {
            return;
        }

        $this->performInsert($this->importableSheetData);
    }

    private function beforeNextSheet()
    {
        $this->importableSheetData = [];
        $this->activeSheetIndex++;
        $this->headingRow = 1;
        $this->startRow = 2;
    }

    private function increaseRowsCount()
    {
        $this->rowsCount++;
    }

    private function checkHeadingRow(Worksheet $worksheet)
    {
        $this->mapHeaders($worksheet);
        $this->mapRequiredHeaders();

        if (!$this->requiredHeadersPresent()) {
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
                return trim($column['header']) === trim($header) && isset($column['value']);
            }) || $columnsData->filter(function ($column) {
                return isset($column['value']);
            })->count() > 3;
        })->isEmpty();

        return $pass;
    }

    private function determineCoveragePeriod(Worksheet $worksheet)
    {
        $headingRow = HeadingRowExtractor::extract($worksheet, $this);

        $coverageReg = '/((?<!from|to)[ ]*coverage[ ]*?(period)?(?!from|to))|((?<!fra|til)[ ]*dæknings[ ]*periode[ ]*(?<!fra|til))/i';
        $coverageExists = preg_grep($coverageReg, $headingRow);

        if (!$coverageExists) {
            return;
        }

        $lang = preg_match('/dæknings/i', reset($coverageExists)) ? 'de' : 'en';

        $nextHeadingRow = head(iterator_to_array($worksheet->getRowIterator($this->headingRow, $this->headingRow)));
        $cellIterator = $nextHeadingRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $foundStart = false;
        $foundEnd = false;
        foreach ($cellIterator as $cell) {
            if (preg_match($coverageReg, $cell->getValue()) && !$foundStart) {
                $cell->setValue(__("parser.coverage_period.{$lang}.from"));
                $foundStart = $cellIterator->getCurrentColumnIndex();
                continue;
            }

            if ($foundStart && $cell->getValue() === null) {
                $foundEnd = $cellIterator->getCurrentColumnIndex();
                continue;
            }

            if ($foundStart && $foundEnd && $cell->getValue() !== null) {
                $end = (int) ($foundStart + floor(($foundEnd - $foundStart) / 2) + 1);
                $worksheet->setCellValueByColumnAndRow($end, $this->headingRow, __("parser.coverage_period.{$lang}.to"));
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
        if ($this->quoteFile->isCsv()) {
            if (Str::length($header) > 100) {
                $this->quoteFile->setException(config('constants.QFWS_01'));
                $this->quoteFile->markAsUnHandled();
                throw new \ErrorException(config('constants.QFWS_01'));
            }
        }

        return trim(Str::limit($header, 40, ''));
    }

    private function makeRow(array $row)
    {
        $row['imported_columns'] = $row['imported_columns']->toArray();

        array_push($this->importableSheetData, $row);
    }

    private function fetchRow(Collection $row)
    {
        $imported_row_id = Uuid::generate(4)->string;
        $now = now()->toDateTimeString();
        $imported_row = [
            'id' => $imported_row_id,
            'user_id' => $this->user->id,
            'quote_file_id' => $this->quoteFile->id,
            'page' => $this->activeSheetIndex,
            'created_at' => $now,
            'updated_at' => $now,
            'processed_at' => $now
        ];

        $imported_columns = $row->map(function ($value, $header) use ($row, $imported_row_id) {
            if (isset($value)) {
                $value = preg_replace('/(^[\h]+)|([\h]+$)/um', '', str_replace('_x000D_', '', $value));
            }

            $id = Uuid::generate(4)->string;
            $importable_column_id = $this->headersMapping->get($header);
            $header = $this->checkAndLimitHeader($header);

            return compact('id', 'imported_row_id', 'importable_column_id', 'value', 'header');
        })->filter(function ($column) {
            return isset($column['importable_column_id']) && $column['header'] !== $column['value'];
        })->values();

        return compact('imported_row', 'imported_columns');
    }

    private function mapHeaders(Worksheet $worksheet)
    {
        $headingRow = collect(HeadingRowExtractor::extract($worksheet, $this));

        $aliasesMapping = $this->importableColumn->allSystem()->pluck('aliases.*.alias', 'id');
        $this->headersMapping = [];
        $mapping = collect([]);

        $this->headersMapping = $headingRow->mapWithKeys(function ($header, $key) use ($aliasesMapping, $mapping) {
            $column = false;
            $column_num = $key + 1;

            if (filled($header)) {
                $column = $aliasesMapping->search(function ($aliases, $importable_column_id) use ($header, $mapping) {
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

            if (!$column) {
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

        $this->headersCount = $mapping->count();
    }

    private function mapRequiredHeaders()
    {
        $this->requiredHeadersMapping = collect($this->requiredHeaders)->mapWithKeys(function ($name) {
            $aliases = $this->systemImportableColumns->where('name', $name)->first()->aliases->pluck('alias');

            $header = $this->headersMapping->search(function ($id, $header) use ($aliases) {
                return $aliases->contains(function ($alias) use ($header) {
                    return preg_match("~^{$alias}.*?~i", $header);
                });
            });

            return [$name => $header];
        });
    }
}
