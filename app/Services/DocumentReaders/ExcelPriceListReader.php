<?php

namespace App\Services\DocumentReaders;

use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Services\DocumentReaders\Models\HeadingRow;
use App\Services\DocumentReaders\Models\Row as DocumentRow;
use App\Services\DocumentReaders\Validation\RowContainsOnePayMention;
use App\Services\DocumentReaders\Validation\RowContainsOnlySerialNumberMention;
use App\Services\DocumentReaders\Validation\RowContainsOnlySubtotal;
use App\Services\DocumentReaders\Validation\RowIsNotSameAsHeader;
use App\Services\DocumentReaders\Validation\RowValidationPayload;
use App\Services\DocumentReaders\Validation\RowValidationPipeline;
use App\Services\DocumentReaders\Validation\RowValuesMatchAnyCombination;
use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\XLSX\RowIterator;
use Box\Spout\Reader\XLSX\Sheet;
use Carbon\Carbon;
use Generator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class ExcelPriceListReader
{
    protected array $requiredHeaderColumnNameCombinations = [
        ['product_no', 'price'],
        ['description', 'date_from', 'date_to']
    ];

    protected ?array $requiredHeaderColumnCombinationsCache = null;

    protected ?array $systemColumnAliasMappingCache = null;

    protected array $customColumnAliasMappingCache = [];

    protected array $headerMappingCache = [];

    /**
     * @param string $filePath
     * @return \Generator|DocumentRow[]
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException
     */
    public function readFile(string $filePath): Generator
    {
        if (\PHP_VERSION_ID < 80000) {
            $libxmlEntityLoaderPreviousValue = libxml_disable_entity_loader(false);
        }

        $reader = ReaderFactory::createFromFile($filePath);

        $reader->open($filePath);

        /** @var \Box\Spout\Reader\XLSX\SheetIterator $sheets */
        $sheets = $reader->getSheetIterator();

        $sheets->rewind();

        foreach ($sheets as $index => $sheet) {

            foreach ($this->readExcelSheet($sheet) as $row) {
                if (is_null($row)) {
                    continue;
                }

                yield $row;
            }

        }

        if (isset($libxmlEntityLoaderPreviousValue)) {
            libxml_disable_entity_loader($libxmlEntityLoaderPreviousValue);
        }
    }

    /**
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException
     * @throws \Box\Spout\Common\Exception\IOException
     */
    protected function readExcelSheet(Sheet $sheet): Generator
    {
        $rows = $sheet->getRowIterator();

        $rows->rewind();

        [$headerMapping, $missingHeaderMapping] = $this->determineSheetHeaderRow($rows);

        if (empty($headerMapping)) {
            return null;
        }

        $headingRow = new HeadingRow(
            array_flip($headerMapping),
            $missingHeaderMapping,
            $sheet->getIndex(),
            $sheet->getName()
        );

        while ($rows->valid()) {
            $valuesOfRow = $this->mapValuesOfRow($rows->current(), $headerMapping);

            if ($this->validateValuesOfRow($valuesOfRow, $headingRow)) {
                yield new DocumentRow(
                    $headingRow,
                    $valuesOfRow
                );
            }

            $rows->next();
        }
    }

    /**
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException
     */
    protected function determineSheetHeaderRow(RowIterator $rowIterator): array
    {
        while ($rowIterator->valid()) {

            $row = $rowIterator->current();

            [$headerRowMapping, $missingHeaderMapping] = $this->mapHeaderRow($row);

            $rowIterator->next();

            if ($this->validateHeaderRow($headerRowMapping)) {

                $nextRow = $rowIterator->current();

                $mergedRow = $this->mergeHeaderRowWithNextRow($row, $nextRow);

                if ($mergedRow === $row) {
                    return [$headerRowMapping, $missingHeaderMapping];
                }

                return $this->mapHeaderRow($mergedRow);

            }

        }

        return [[], []];
    }

    protected function mergeHeaderRowWithNextRow(Row $headerRow, Row $nextRow): Row
    {
//        dd($headerRow->toArray(), $nextRow->toArray());

        $mergedColumnIndexes = value(function () use ($headerRow, $nextRow): array {

            $headerRowValues = $headerRow->toArray();

            foreach ($headerRowValues as $key => $cellValue) {
                $coveragePeriodColumnIsMerged = Str::is(['*coverage*period*', '*dæknings*periode*'], mb_strtolower($cellValue));

                $nextColumnIsBlank = isset($headerRowValues[$key+1]) && trim($headerRowValues[$key+1]) === "";

                if ($coveragePeriodColumnIsMerged && $nextColumnIsBlank) {

                    return [$key, $key+1];

                }
            }

            return [];

        });

        if (empty($mergedColumnIndexes)) {
            return $headerRow;
        }

        $mergedCells = array_map(fn (Cell $cell) => clone $cell, $headerRow->getCells());

        foreach ($mergedCells as $index => $cell) {

            if (is_null($cellFromNextRow = $nextRow->getCellAtIndex($index))) {
                continue;
            }

            $cellValue = $cell->getValue();
            $cellValueFromNextRow = $cellFromNextRow->getValue();

            if ((!is_string($cellValue) && !is_null($cellValue)) || (!is_string($cellValueFromNextRow) && !is_null($cellValueFromNextRow))) {
                continue;
            }

            $mergedCellValue = implode(' ',[trim((string)$cellValue), trim((string)$cellValueFromNextRow)]);

            $cell->setValue(trim($mergedCellValue));
        }

        return new Row($mergedCells, clone $headerRow->getStyle());
    }

    protected function mapHeaderRow(Row $row): array
    {
        $mapping = [];
        $missingHeaderMapping = [];

        foreach ($row->toArray() as $key => $cellValue) {
            if (false === is_string($cellValue) || trim($cellValue) === '') {
                $headerNumber = $key + 1;
                $headerName = "Unknown header $headerNumber";

                $mapping[$headerName] = $missingHeaderMapping[] = $this->mapHeaderColumnWithImportableColumn($headerName, $mapping);

                continue;
            }

            $mapping[$cellValue] = $this->mapHeaderColumnWithImportableColumn($cellValue, $mapping);
        }

        return [$mapping, $missingHeaderMapping];
    }

    protected function mapHeaderColumnWithImportableColumn(string $header, array $allocatedColumnKeys = []): string
    {
        if (isset($this->headerMappingCache[$header]) && !in_array($this->headerMappingCache[$header], $allocatedColumnKeys, true)) {
            return $this->headerMappingCache[$header];
        }

        $allocatedColumnDictionary = array_fill_keys(array_values($allocatedColumnKeys), true);

        $columnKey = value(function () use ($allocatedColumnDictionary, $header) {
            $aliasMapping = $this->getSystemColumnAliasMapping() + $this->getCustomColumnAliasMapping($header);

            $knownColumn = value(function () use ($allocatedColumnDictionary, $header, $aliasMapping): ?string {

                $header = str_replace(["\n"], [" "], $header);

                foreach ($aliasMapping as $alias => $key) {
                    if (array_key_exists($key, $allocatedColumnDictionary)) {
                        continue;
                    }

                    $quotedAlias = preg_quote($alias, '#');
                    $pattern = "#^$quotedAlias(?![^\h]).*#i";

                    if (preg_match($pattern, $header)) {
                        return $key;
                    }

                }

                return null;

            });

            if (!is_null($knownColumn)) {
                return $knownColumn;
            }

            $importableColumn = tap(new ImportableColumn(), function (ImportableColumn $column) use ($header) {
                $column->{$column->getKeyName()} = (string)Uuid::generate(4);
                $column->is_temp = true;
                $column->name = Str::slug($header, '_');
                $column->header = trim($header);
                $column->is_system = false;

                $column->saveQuietly();

                tap(new ImportableColumnAlias(), function (ImportableColumnAlias $alias) use ($column, $header) {
                    $alias->{$alias->getKeyName()} = (string)Uuid::generate(4);
                    $alias->importableColumn()->associate($column);
                    $alias->alias = $header;

                    $alias->saveQuietly();
                });

            });

            return $importableColumn->getKey();
        });

        return $this->headerMappingCache[$header] = $columnKey;
    }

    private function getSystemColumnAliasMapping(): array
    {
        return $this->systemColumnAliasMappingCache ??= ImportableColumnAlias::query()
            ->join('importable_columns', function (JoinClause $join) {
                $join->on('importable_columns.id', 'importable_column_aliases.importable_column_id')
                    ->whereNull('importable_columns.deleted_at')
                    ->where('importable_columns.is_system', true);
            })
            ->select([
                'importable_column_aliases.alias',
                'importable_columns.id'
            ])
            ->pluck('importable_columns.id', 'importable_column_aliases.alias')
            ->all();
    }

    private function getCustomColumnAliasMapping(string $header): array
    {
        return $this->customColumnAliasMappingCache[$header] ??= ImportableColumnAlias::query()
            ->join('importable_columns', function (JoinClause $join) {
                $join->on('importable_columns.id', 'importable_column_aliases.importable_column_id')
                    ->whereNull('importable_columns.deleted_at')
                    ->where('importable_columns.is_system', false);
            })
            ->select([
                'importable_column_aliases.alias',
                'importable_columns.id'
            ])
            ->limit(2)
            ->pluck('importable_columns.id', 'importable_column_aliases.alias')
            ->all();
    }

    protected function validateHeaderRow(array $headerRowMapping): bool
    {
        $requiredHeaderColumns = $this->getRequiredHeaderCombinations();

        $requiredColumnsArePresent = value(function () use ($headerRowMapping, $requiredHeaderColumns) {

            foreach ($requiredHeaderColumns as $combination) {

                $combinationMatched = value(function () use ($headerRowMapping, $combination) {

                    foreach ($combination as $name => $key) {
                        if (false === in_array($key, $headerRowMapping)) {
                            return false;
                        }
                    }

                    return true;

                });

                if (true === $combinationMatched) {
                    return true;
                }
            }

            return false;
        });

        if (true === $requiredColumnsArePresent) {
            return true;
        }

        return false;
    }

    private function getRequiredHeaderCombinations(): array
    {
        return $this->requiredHeaderColumnCombinationsCache ??= value(function () {

            $columns = ImportableColumn::query()
                ->where('is_system', true)
                ->whereIn('name', Arr::flatten($this->requiredHeaderColumnNameCombinations))
                ->pluck('id', 'name')
                ->all();

            return array_map(function (array $columnCombination) use ($columns) {
                $combinationDictionary = [];

                foreach ($columnCombination as $columnName) {
                    $combinationDictionary[$columnName] = $columns[$columnName];
                }

                return $combinationDictionary;
            }, $this->requiredHeaderColumnNameCombinations);

        });
    }

    protected function mapValuesOfRow(Row $row, array $headerMapping): array
    {
        $values = $row->toArray();

        ksort($values);

        $values = array_slice($values, 0, count($headerMapping));

        while (count($values) < count($headerMapping)) {
            $values[] = null;
        }

        // Transform all values of row to scalar.
        $values = array_map(function ($value) {
            if (is_scalar($value)) {
                return $value;
            }

            if ($value instanceof \DateTimeInterface) {
                return value(function () use ($value) {
                    $value = Carbon::instance($value);

                    $hasTime = $value->hour !== 0 || $value->minute !== 0 || $value->second !== 0 || $value->microsecond !== 0;

                    if (false === $hasTime) {
                        return $value->toDateString();
                    }

                    return $value->toDateTimeString();
                });
            }

            return null;
        }, $values);

        return array_combine($headerMapping, $values);
    }

    protected function validateValuesOfRow(array $rowValues, HeadingRow $headingRow): bool
    {
        $requiredHeaderColumns = $this->getRequiredHeaderCombinations();

        $validationPayload = new RowValidationPayload($headingRow, $rowValues, $requiredHeaderColumns);

        return (new RowValidationPipeline())
            // Computing the difference of row values with heading row values,
            // The validation should fail if they are exactly the same.
            ->pipe(new RowIsNotSameAsHeader())
            // When the row values contain mention about the one pay row,
            // The validation should be passed without any additional checks.
            ->pipe(new RowContainsOnePayMention())
            // When the row values contain mention about the serial number,
            // The validation should be passed without any additional checks.
            ->pipe(new RowContainsOnlySerialNumberMention())
            // Checking the filled values in the required columns,
            // The validation should pass if any of the combination is passed.
            ->pipe(new RowValuesMatchAnyCombination())
            // When the row values contain only subtotal value,
            // The validation should fail.
            ->pipe(new RowContainsOnlySubtotal())
            ->process($validationPayload);
    }


}
