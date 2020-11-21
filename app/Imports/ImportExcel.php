<?php

namespace App\Imports;

use Maatwebsite\Excel\{
    Row,
    Concerns\WithHeadingRow,
    Concerns\Importable,
    Concerns\WithEvents,
    Concerns\WithChunkReading,
    Events\BeforeSheet,
    Events\AfterImport,
    Imports\HeadingRowFormatter,
    Imports\HeadingRowExtractor,
};
use App\Imports\Concerns\{
    MapsHeaders,
    LimitsHeaders
};
use Maatwebsite\Excel\Concerns\{ToModel, WithBatchInserts, WithLimit, WithStartRow, WithCalculatedFormulas, WithColumnLimit, };
use App\Models\{QuoteFile\QuoteFile, QuoteFile\ImportedRow};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\{Arr, Str, Collection};
use voku\helper\ASCII;

class ImportExcel implements ToModel, WithHeadingRow, WithEvents, WithBatchInserts, WithChunkReading, WithStartRow, WithLimit, WithColumnLimit
{
    use Importable, MapsHeaders, LimitsHeaders;

    const C_IMPC = 'importable_column_id';

    const C_HDR = 'header';

    const C_VL = 'value';

    const C_OP = 'is_one_pay';

    /** @var \App\Models\QuoteFile\QuoteFile */
    protected $quoteFile;

    /** @var \App\Models\User */
    protected $user;

    /** @var \Illuminate\Database\Eloquent\Collection */
    protected $systemImportableColumns;

    /** @var integer */
    protected $activeSheetIndex = 0;

    /** @var integer */
    protected $headingRow = 1;

    /** @var integer */
    protected $startRow = 2;

    /** @var array */
    protected $requiredHeaders = [
        'price', 'product_no'
    ];

    /** @var \Illuminate\Support\Collection */
    protected $header;

    /** @var \Illuminate\Support\Collection */
    protected $requiredHeadersMapping;

    /** @var integer */
    protected $rowsCount = 0;

    /** @var array */
    protected $priceAttributes = [];

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile;
        $this->user = $quoteFile->user;
        $this->systemImportableColumns = $this->importRepository()->allSystem();

        HeadingRowFormatter::
        default('none');
    }

    public function model(array $row)
    {
        $collection = collect($row);

        $importableRow = $this->fetchRow($collection);

        if (!$this->checkColumnsData($importableRow['columns_data'])) {
            return null;
        }

        return tap(ImportedRow::make($importableRow), function () {
            $this->incrementRowsCount();
        });
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->beforeNextSheet($event->sheet->getDelegate());
            },
            AfterImport::class => function (AfterImport $event) {
                $this->afterImport();
            }
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function limit(): int
    {
        return 100000;
    }

    public function endColumn(): string
    {
        return 'BZ';
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    protected function beforeNextSheet(Worksheet $sheet): void
    {
        $this->activeSheetIndex++;
        $this->headingRow = 1;
        $this->startRow = 2;

        $highestRow = $sheet->getHighestRow();

        while (!$this->checkHeadingRow($sheet) && $this->headingRow < $highestRow) {
            $this->findPriceAttributes($this->getStartRow($sheet));

            $this->headingRow++;
            $this->startRow++;

            continue;
        }

        if (intval($highestRow) !== $this->headingRow) {
            $this->determineCoveragePeriod($sheet);
        }

        $this->setSheetHeader($sheet);
        $this->mapHeaders();
    }

    protected function getStartRow(Worksheet $sheet): Collection
    {
        $headingRow = HeadingRowExtractor::extract($sheet, $this);

        $iterator = $sheet->getRowIterator()->resetStart($this->startRow)->resetEnd($this->startRow);

        foreach ($iterator as $row) {
            return (new Row($row, $headingRow))->toCollection();
        }
    }

    protected function afterImport(): void
    {
        if ($this->rowsCount < 1) {
            tap($this->quoteFile)->setException(QFNRF_01, 'QFNRF_01')->markAsUnHandled();
        }

        $this->quoteFile->storeMetaAttributes($this->priceAttributes);
    }

    protected function incrementRowsCount(): void
    {
        $this->rowsCount++;
    }

    protected function checkHeadingRow(Worksheet $worksheet): bool
    {
        $this->setSheetHeader($worksheet);
        $this->mapRequiredHeaders();

        return $this->requiredHeadersPresent();
    }

    protected function checkColumnsData(Collection $columnsData): bool
    {
        if ($columnsData->contains(static::C_OP, true)) {
            return true;
        }

        $headerValue = $columnsData->pluck(static::C_VL, static::C_IMPC)->filter(fn ($value) => filled($value));

        $columnsCount = $headerValue->count();

        if ($columnsCount < count($this->requiredHeadersMapping)) {
            return false;
        }

        foreach ($this->requiredHeadersMapping as $header => $value) {
            if (is_null($value)) {
                return false;
            }

            $id = $this->headersMapping->get($value);

            if (!$headerValue->has($id)) {
                return false;
            }
        }

        return true;
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
    }

    protected function requiredHeadersPresent(): bool
    {
        return $this->requiredHeadersMapping->reject(fn ($value) => $value)->isEmpty();
    }

    protected function makeRow(array $row): void
    {
        array_push($this->importableSheetData, $row);
    }

    protected function fetchRow(Collection $row): array
    {
        $now = now()->toDateTimeString();

        $columnsData = $this->makeColumnsData($row);

        $onePay = $columnsData->contains(static::C_OP, true);

        return [
            'id'            => Uuid::generate(4)->string,
            'user_id'       => $this->user->id,
            'quote_file_id' => $this->quoteFile->id,
            'page'          => $this->activeSheetIndex,
            'columns_data'  => $columnsData,
            static::C_OP    => $onePay,
            'created_at'    => $now,
            'updated_at'    => $now
        ];
    }

    protected function setSheetHeader(Worksheet $worksheet): void
    {
        $this->header = HeadingRowExtractor::extract($worksheet, $this);
    }

    protected function mapRequiredHeaders(): void
    {
        $this->requiredHeadersMapping = collect($this->requiredHeaders)->mapWithKeys(function ($name) {
            $aliases = $this->systemImportableColumns->firstWhere('name', $name)->aliases->pluck('alias');

            $aliasesParts = $aliases->map(fn ($alias) => '(' . preg_quote($alias, '~') . '.*?)')->implode('|');
            $aliasesExpression = "~^{$aliasesParts}~i";

            $header = Arr::first($this->header, fn ($header) => preg_match($aliasesExpression, $header), null);

            return [$name => $header];
        });
    }

    protected function findPriceAttributes(Collection $row): void
    {
        $foundAttributes = [
            'service_agreement_id' => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_SAID, ImportExcelOptions::REGEXP_SAID_VALUE, $row),
            'pricing_document'     => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_PD, ImportExcelOptions::REGEXP_PD_VALUE, $row),
            'system_handle'        => (array) $this->findRowAttribute(ImportExcelOptions::REGEXP_SH, ImportExcelOptions::REGEXP_SH_VALUE, $row),
        ];

        $attributes = array_merge_recursive(array_filter($this->priceAttributes), $foundAttributes);

        $this->priceAttributes = array_map(function ($attribute) {
            return array_values(array_flip(array_flip(array_filter($attribute))));
        }, $attributes);
    }

    private function makeColumnsData($attributes): Collection
    {
        $attributes = Collection::wrap($attributes);

        $columns = $attributes->map(
            fn ($value, $header) =>
            [
                static::C_IMPC  => $this->headersMapping->get($header),
                static::C_HDR   => $this->limitHeader($this->quoteFile, $header),
                static::C_VL    => static::sanitizeColumnValue($value),
            ]
        )
            ->filter(fn ($column) => isset($column[static::C_IMPC]) && $column[static::C_HDR] !== $column[static::C_VL])
            ->keyBy(static::C_IMPC);

        $hasOnePayColumn = $columns->contains(fn ($column) => preg_match('/return to/i', data_get($column, static::C_VL)));

        if ($hasOnePayColumn && null !== ($priceHeader = $this->requiredHeadersMapping->get('price'))) {
            $currentPriceColumn = $columns->whereNotNull(static::C_HDR)->first(fn ($column) => trim($column[static::C_HDR]) === trim($priceHeader));

            if (null !== $currentPriceColumn && !is_null($currentPriceColumn[static::C_VL])) {
                $currentPriceColumn = array_merge($currentPriceColumn, [static::C_OP => true]);

                $columns->put($currentPriceColumn[static::C_IMPC], $currentPriceColumn);

                return $columns;
            }

            /**
             * In case if price column is not present in One Pay row, we will assume that price cell is merged with qty.
             * 
             * @var array|null
             */
            $priceColumn = $columns->first(fn ($column) => Str::containsInsensitive(data_get($column, static::C_HDR), 'qty'));

            if ($priceColumn === null || $priceHeader === $priceColumn[static::C_HDR]) {
                return $columns;
            }

            if (blank($priceColumn[static::C_VL]) && null !== $currentPriceColumn) {
                $currentPriceColumn = array_merge($currentPriceColumn, [static::C_OP => true]);

                $columns->put($currentPriceColumn[static::C_IMPC], $currentPriceColumn);

                return $columns;
            }

            $columns->forget($priceColumn[static::C_IMPC]);

            $priceColumn = array_merge($priceColumn, [
                static::C_IMPC  => $columnId = $this->headersMapping->get($priceHeader),
                static::C_HDR   => $priceHeader,
                static::C_OP    => true
            ]);

            $columns->put($columnId, $priceColumn);
        }

        return $columns;
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

    private static function sanitizeColumnValue($value)
    {
        if (is_string($value)) {
            if (!ASCII::is_ascii($value)) {
                $value = trim(ASCII::to_ascii($value));
            }

            $value = preg_replace('/(^[\h]+)|([\h]+$)/um', '', str_replace('_x000D_', '', $value));
        }

        return $value;
    }
}
