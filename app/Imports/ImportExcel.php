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
use Arr, DB, Str;

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
    protected $chunkSize = 100;

    /**
     * Price Meta Attributes.
     *
     * @var array
     */
    protected $priceAttributes = [];

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile;
        $this->user = $quoteFile->user;
        $this->systemImportableColumns = $this->importRepository()->allSystem();

        HeadingRowFormatter::
            default('none');
    }

    public function onRow(Row $excelRow)
    {
        $row = $excelRow->toCollection(null, true);

        if ($excelRow->getIndex() < $this->startRow) {
            $this->findPriceAttributes($row);
            return;
        }

        $importableRow = $this->fetchRow($row);

        if (!$this->checkColumnsData($importableRow['columns_data'])) {
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
                $this->beforeNextSheet($event->sheet->getDelegate());
            },
            AfterSheet::class => function ($event) {
                $this->importSheetData();
            },
            AfterImport::class => function ($event) {
                $this->afterImport();
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
        if (count($this->importableSheetData) < $this->chunkSize) {
            return;
        }

        $this->performInsert(array_splice($this->importableSheetData, 0, $this->chunkSize));
    }

    protected function performInsert(array $importableRows): void
    {
        DB::table('imported_rows')->insert($importableRows);
    }

    protected function importSheetData(): void
    {
        if (empty($this->importableSheetData)) {
            return;
        }

        $this->performInsert($this->importableSheetData);
    }

    protected function beforeNextSheet(Worksheet $sheet): void
    {
        $this->importableSheetData = [];
        $this->activeSheetIndex++;
        $this->headingRow = 1;
        $this->startRow = 2;

        $highestRow = $sheet->getHighestRow();

        while (!$this->checkHeadingRow($sheet) && $this->headingRow < $highestRow) {
            continue;
        }

        $this->determineCoveragePeriod($sheet);
    }

    protected function afterImport(): void
    {
        if ($this->rowsCount < 1) {
            tap($this->quoteFile)->setException(QFNRF_01, 'QFNRF_01')
                ->markAsUnHandled();
        }

        $this->quoteFile->storeMetaAttributes($this->priceAttributes);
    }

    protected function increaseRowsCount(): void
    {
        $this->rowsCount++;
    }

    protected function checkHeadingRow(Worksheet $worksheet): bool
    {
        $this->setHeader($worksheet);
        $this->mapRequiredHeaders();

        if (!$this->requiredHeadersPresent()) {
            $this->startRow++;
            $this->headingRow++;
            return false;
        }

        $this->mapHeaders();

        return true;
    }

    protected function checkColumnsData(Collection $columnsData): bool
    {
        return $this->requiredHeadersMapping->reject(function ($header, $id) use ($columnsData) {
            return $columnsData->contains(fn ($column) => trim($column['header']) === trim($header) && isset($column['value']))
                || $columnsData->filter(fn ($column) => isset($column['value']))->count() > 3;
        })->isEmpty();
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
        array_push($this->importableSheetData, $row);
    }

    protected function fetchRow(Collection $row): array
    {
        return $this->makeImportedRow() + ['columns_data' => $this->makeColumnsData($row)];
    }

    protected function setHeader(Worksheet $worksheet): void
    {
        $this->header = HeadingRowExtractor::extract($worksheet, $this);
    }

    protected function mapRequiredHeaders(): void
    {
        $this->requiredHeadersMapping = collect($this->requiredHeaders)->mapWithKeys(function ($name) {
            $aliases = $this->systemImportableColumns->firstWhere('name', $name)->aliases->pluck('alias');

            $header = Arr::first($this->header, function ($header) use ($aliases) {
                return $aliases->contains(function ($alias) use ($header) {
                    return preg_match("~^{$alias}.*?~i", $header);
                });
            }, false);

            return [$name => $header];
        });
    }

    protected function findPriceAttributes(Collection $row): void
    {
        $foundAttributes = [
            'service_agreement_id'  => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_SAID, ImportExcelOptions::REGEXP_SAID_VALUE, $row),
            'pricing_document'      => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_PD, ImportExcelOptions::REGEXP_PD_VALUE, $row),
            'system_handle'         => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_SH, ImportExcelOptions::REGEXP_SH_VALUE, $row),
        ];

        $attributes = array_merge_recursive(array_filter($this->priceAttributes), $foundAttributes);

        $this->priceAttributes = array_map(function ($attribute) {
            return array_values(array_flip(array_flip(array_filter($attribute))));
        }, $attributes);
    }

    private function makeImportedRow(): array
    {
        $now = now()->toDateTimeString();

        return [
            'id'            => Uuid::generate(4)->string,
            'user_id'       => $this->user->id,
            'quote_file_id' => $this->quoteFile->id,
            'page'          => $this->activeSheetIndex,
            'created_at'    => $now,
            'updated_at'    => $now
        ];
    }

    private function makeColumnsData($attributes): Collection
    {
        $attributes = Collection::wrap($attributes);

        return $attributes->map(
            fn ($value, $header) =>
            [
                'importable_column_id'  => $this->headersMapping->get($header),
                'header'                => $this->limitHeader($this->quoteFile, $header),
                'value'                 => static::sanitizeColumnValue($value)
            ]
        )
            ->filter(fn ($column) => isset($column['importable_column_id']) && $column['header'] !== $column['value'])
            ->keyBy('importable_column_id');
    }

    private function findRowAttribute(string $regexp, string $cellRegexp, Collection $row)
    {
        if (filled($matches = preg_grep($regexp, $row->toArray(null, true)))) {
            $cell = head($matches);

            if (preg_match($cellRegexp, $cell, $cellMatches)) {
                return Str::trim(last($cellMatches));
            }

            $key = $row->values()->search($cell) + 1;

            return Str::trim($row->slice($key)->filter()->first(function ($col) {
                return !is_null($col);
            }));
        }

        return null;
    }

    private static function sanitizeColumnValue(?string $value)
    {
        if (blank($value)) {
            return $value;
        }

        return preg_replace('/(^[\h]+)|([\h]+$)/um', '', str_replace('_x000D_', '', $value));
    }
}
