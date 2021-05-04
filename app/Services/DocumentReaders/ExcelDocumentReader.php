<?php

namespace App\Services\DocumentReaders;

use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Services\DocumentReaders\Models\HeadingRow;
use App\Services\DocumentReaders\Models\Row as DocumentRow;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\XLSX\RowIterator;
use Box\Spout\Reader\XLSX\Sheet;
use Generator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class ExcelDocumentReader
{
    protected array $requiredHeaderColumnNames = ['product_no', 'price'];

    protected ?array $requiredHeaderColumnsCache = null;

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

        $headerMapping = $this->determineSheetHeaderRow($rows);

        if (empty($headerMapping)) {
            return null;
        }

        $rows->next();

        $headingRow = new HeadingRow(
            array_flip($headerMapping),
            $sheet->getIndex(),
            $sheet->getName()
        );

        while ($rows->valid()) {
            $valuesOfRow = $this->mapValuesOfRow($rows->current(), $headerMapping);

            if ($this->validateValuesOfRow($valuesOfRow)) {
                yield new DocumentRow(
                    $headingRow,
                    $valuesOfRow
                );
            }

            $rows->next();
        }
    }

    protected function validateValuesOfRow(array $rowValues): bool
    {
        $requiredHeaderColumns = $this->getRequiredHeaderColumns();

        $containsReferenceAboutOnePay = value(function () use ($rowValues): bool {
            foreach ($rowValues as $value) {
                if (preg_match('/return to/i', $value)) {
                    return true;
                }
            }

            return false;
        });

        if ($containsReferenceAboutOnePay) {
            return true;
        }

        foreach ($requiredHeaderColumns as $name => $key) {
            if ((false === isset($rowValues[$key])) || trim((string)$rowValues[$key]) === '') {
                return false;
            }
        }

        return true;
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
                return $value->format(DATE_ISO8601);
            }

            return null;
        }, $values);

        return array_combine($headerMapping, $values);
    }

    /**
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException
     */
    protected function determineSheetHeaderRow(RowIterator $rowIterator): array
    {
        $requiredHeaderColumns = $this->getRequiredHeaderColumns();

        while ($rowIterator->valid()) {

            $row = $rowIterator->current();

            $headerRowMapping = $this->mapHeaderRow($row);

            $requiredColumnsArePresent = value(function () use ($headerRowMapping, $requiredHeaderColumns) {

                foreach ($requiredHeaderColumns as $name => $key) {
                    if (false === in_array($key, $headerRowMapping)) {
                        return false;
                    }
                }

                return true;
            });

            if ($requiredColumnsArePresent) {
                return $headerRowMapping;
            }

            $rowIterator->next();

        }

        return [];
    }

    protected function mapHeaderRow(Row $row): array
    {
        $mapping = [];

        foreach ($row->toArray() as $key => $cellValue) {
            if (false === is_string($cellValue) || trim($cellValue) === '') {
                $headerNumber = $key + 1;
                $headerName = "Unknown header $headerNumber";

                $mapping[$headerName] = $this->mapHeaderColumnWithImportableColumn($headerName, $mapping);

                continue;
            }

            $mapping[$cellValue] = $this->mapHeaderColumnWithImportableColumn($cellValue, $mapping);
        }

        return $mapping;
    }

    protected function mapHeaderColumnWithImportableColumn(string $header, array $allocatedColumnKeys = []): string
    {
        if (isset($this->headerMappingCache[$header]) && !in_array($this->headerMappingCache[$header], $allocatedColumnKeys, true)) {
            return $this->headerMappingCache[$header];
        }

        $columnKey = value(function () use ($header, $allocatedColumnKeys) {
            $aliasMapping = $this->getSystemColumnAliasMapping() + $this->getCustomColumnAliasMapping($header);

            $knownColumn = value(function () use ($header, $aliasMapping): ?string {

                foreach ($aliasMapping as $alias => $key) {
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

    private function getRequiredHeaderColumns(): array
    {
        return $this->requiredHeaderColumnsCache ??= ImportableColumn::query()
            ->where('is_system', true)
            ->whereIn('name', $this->requiredHeaderColumnNames)
            ->pluck('id', 'name')
            ->all();
    }


}
