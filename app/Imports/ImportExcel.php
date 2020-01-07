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
use App\Imports\Concerns\{
    MapsHeaders,
    LimitsHeaders
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webpatser\Uuid\Uuid;
use Arr, DB;

class ImportExcel implements OnEachRow, WithHeadingRow, WithEvents, WithChunkReading
{
    use Importable, MapsHeaders, LimitsHeaders;

    /**
     * QuoteFile instance.
     *
     * @var \App\Models\QuoteFile\QuoteFile
     */
    protected $quoteFile;

    /**
     * User has importing QuoteFile.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * Retrieved System Importable Columns from repository.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $systemImportableColumns;

    /**
     * Actual Sheet Index.
     *
     * @var integer
     */
    protected $activeSheetIndex = 0;

    /**
     * Heading Row index.
     *
     * @var integer
     */
    protected $headingRow = 1;

    /**
     * Start row index.
     *
     * @var integer
     */
    protected $startRow = 2;

    /**
     * Required Header for importing.
     *
     * @var array
     */
    protected $requiredHeaders = [
        'price', 'product_no'
    ];

    /**
     * Actual Header.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $header;

    /**
     * Mapping for Required Headers on Row for importing.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $requiredHeadersMapping;

    /**
     * Total Imported Rows Count.
     *
     * @var integer
     */
    protected $rowsCount = 0;

    /**
     * Accumulated Sheet Data should be inserted into DB.
     *
     * @var array
     */
    protected $importableSheetData = [];

    /**
     * Minumum accumulated Sheet Data Rows should be inserted into DB in each tick.
     *
     * @var integer
     */
    protected $chunkSize = 500;

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile->load('user');
        $this->user = $quoteFile->user;
        $this->systemImportableColumns = $this->importRepository()->allSystem();

        HeadingRowFormatter::
        default('none');
    }

    public function onRow(Row $row)
    {
        if ($row->getIndex() < $this->startRow) {
            return;
        }

        $row = $row->toCollection(null, true);
        $importableRow = $this->fetchRow($row);

        if (!$this->checkColumnsData($importableRow['imported_columns'])) {
            return;
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
                    $this->quoteFile->setException(QFNRF_01, 'QFNRF_01');
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

    protected function performChunkInsert(): void
    {
        if (count(Arr::pluck($this->importableSheetData, 'imported_columns'), true) < $this->chunkSize * $this->headersCount) {
            return;
        }

        $this->performInsert(array_splice($this->importableSheetData, 0, $this->chunkSize));
    }

    protected function performInsert(array $data): void
    {
        $importableRows = Arr::pluck($data, 'imported_row');
        $importableColumns = Arr::collapse(Arr::pluck($data, 'imported_columns.*'));

        DB::table('imported_rows')->insert($importableRows);
        DB::table('imported_columns')->insert($importableColumns);
    }

    protected function importSheetData(): void
    {
        if (empty($this->importableSheetData)) {
            return;
        }

        $this->performInsert($this->importableSheetData);
    }

    protected function beforeNextSheet(): void
    {
        $this->importableSheetData = [];
        $this->activeSheetIndex++;
        $this->headingRow = 1;
        $this->startRow = 2;
    }

    protected function increaseRowsCount(): void
    {
        $this->rowsCount++;
    }

    protected function checkHeadingRow(Worksheet $worksheet): bool
    {
        $this->setHeader($worksheet);
        $this->mapHeaders();
        $this->mapRequiredHeaders();

        if (!$this->requiredHeadersPresent()) {
            $this->startRow++;
            $this->headingRow++;
            return false;
        }

        return true;
    }

    protected function checkColumnsData(Collection $columnsData): bool
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

    protected function determineCoveragePeriod(Worksheet $worksheet): void
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

        $this->setHeader($worksheet);
        $this->mapHeaders();
    }

    protected function requiredHeadersPresent(): bool
    {
        return $this->requiredHeadersMapping->reject(function ($value, $key) {
            return $value;
        })->isEmpty();
    }

    protected function makeRow(array $row): void
    {
        $row['imported_columns'] = $row['imported_columns']->toArray();

        array_push($this->importableSheetData, $row);
    }

    protected function fetchRow(Collection $row): array
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
            $header = $this->limitHeader($this->quoteFile, $header);

            return compact('id', 'imported_row_id', 'importable_column_id', 'value', 'header');
        })->filter(function ($column) {
            return isset($column['importable_column_id']) && $column['header'] !== $column['value'];
        })->values();

        return compact('imported_row', 'imported_columns');
    }

    protected function setHeader(Worksheet $worksheet): void
    {
        $this->header = HeadingRowExtractor::extract($worksheet, $this);
    }

    protected function mapRequiredHeaders(): void
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
