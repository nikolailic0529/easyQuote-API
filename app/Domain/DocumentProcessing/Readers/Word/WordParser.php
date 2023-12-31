<?php

namespace App\Domain\DocumentProcessing\Readers\Word;

use App\Domain\DocumentProcessing\Contracts\WordParserInterface;
use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\{Collection as SupportCollection};
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use voku\helper\ASCII;

class WordParser implements WordParserInterface
{
    public const MIN_HEADERS_COUNT = 3;

    public const MAX_MISSING_CELLS = 4;

    public const SYSTEM_HEADER_ONE_PAY = '_one_pay';

    public const REGEXP_ONE_PAY = '/return to/i';

    protected PhpWord $phpWord;

    protected static string $defaultSeparator = "\t";

    public function load(string $path): static
    {
        $this->phpWord = IOFactory::load($path);

        return $this;
    }

    public function parseAsDistributorFile(string $filePath): array
    {
        $columns = ImportableColumn::query()
            ->orderBy('order')
            ->where('is_system', true)
            ->with('aliases')
            ->get();

        $this->load($filePath);

        $tables = $this->getTables();

        if (empty($tables)) {
            return [];
        }

        $rows = $this->getRows($columns, $tables);

        $page = 1;
        $content = null;

        $rowsLines = [];
        $rowsLines[] = implode(static::$defaultSeparator, $rows['header']);

        foreach ($rows['rows'] as $row) {
            $rowsLines[] = implode(static::$defaultSeparator, $row['cells']);
        }

        $content = implode(PHP_EOL, $rowsLines);

        $rawPages = [compact('page', 'content')];

        return $rawPages;
    }

    public function getTables(?PhpWord $phpWord = null): array
    {
        $phpWord ??= $this->phpWord;

        $tables = [];

        foreach ($phpWord->getSections() as $section) {
            $tables = array_merge($tables, $this->extractTablesFromElement($section));
        }

        return $this->parseTableElements($tables);
    }

    public function getRows(Collection $columns, array $tableElements, WordParser $wordParser = null): array
    {
        $wordParser ??= $this;

        $rows = [];

        foreach ($columns as $column) {
            $aliases = $column->aliases;

            $foundRowsByColumn = [];

            foreach ($aliases as $alias) {
                $foundRowsByAlias = $wordParser->findRows($alias->alias, $tableElements);

                if (!empty($foundRowsByAlias)) {
                    $foundRowsByColumn = $foundRowsByAlias;
                    break;
                }
            }

            if (!empty($foundRowsByColumn)) {
                $rows = $foundRowsByColumn;
                break;
            }
        }

        return $rows;
    }

    public function findRows(string $needle, array $tables = []): array
    {
        $cellPath = $this->findCell($tables, $needle);

        if (!$cellPath) {
            return [];
        }

        $header = $this->normalizeHeader($tables, $cellPath);

        if (count($header) < static::MIN_HEADERS_COUNT) {
            return [];
        }

        /* We are appending system _one_pay header to determine case when a row is one off pay. */
        $header[] = static::SYSTEM_HEADER_ONE_PAY;

        $rowsPath = array_splice($cellPath, 0, -3);

        $rows = $tables;

        foreach ($rowsPath as $key) {
            $rows = $rows[$key];
        }

        /**
         * Remove header row.
         */
        $rows = array_splice($rows, 1);

        $rows = $this->filterRows($rows, $header);

        return compact('header', 'rows');
    }

    private function normalizeHeader(array $tables, array $path)
    {
        $path = array_splice($path, 0, -1);
        $row = $tables;

        foreach ($path as $key) {
            $row = $row[$key];
        }

        $row = $this->simplifyRow($row);

        $row = $this->findCoverage($row);

        return $row;
    }

    private function findCoverage(array $row)
    {
        $coverageSearch = preg_grep('/^coverage period|fakturering/i', $row);

        if (empty($coverageSearch)) {
            return $row;
        }

        $coverageKey = array_keys($coverageSearch)[0];

        $beforeCoverage = array_slice($row, 0, $coverageKey);

        $afterCoverage = array_slice($row, $coverageKey);
        array_shift($afterCoverage);

        $coverage = ['Coverage period from', 'Coverage period to'];

        $afterCoverage = array_merge($coverage, $afterCoverage);

        return array_merge($beforeCoverage, $afterCoverage);
    }

    private function simplifyRow(array $row)
    {
        $simpleRow = [];

        foreach ($row as $cell) {
            array_push($simpleRow, $this->extractTextValue($cell));
        }

        return $simpleRow;
    }

    private function extractTextValue(array $array)
    {
        if (isset($array['text'])) {
            return $array['text'];
        }

        foreach ($array as $key => $value) {
            if ($key === 'text') {
                return $value['text'];
            }

            if (is_array($value) && $result = $this->extractTextValue($value)) {
                return $result;
            }
        }

        return false;
    }

    private function findCell(array $tables, string $needle, array $path = [])
    {
        $needle = \mb_strtolower($needle);

        foreach ($tables as $key => $value) {
            $currentPath = array_merge($path, [$key]);

            if (is_array($value) && $result = $this->findCell($value, $needle, $currentPath)) {
                return $result;
            }

            if (
                isset($value['text']) &&
                \mb_strtolower($value['text']) === $needle
            ) {
                return $currentPath;
            }
        }

        return false;
    }

    private function readTable(Table $table)
    {
        $instanceRows = $table->getRows();
        $rows = [];

        foreach ($instanceRows as $rowElement) {
            $row = [];
            $cells = [];

            foreach ($rowElement->getCells() as $cellElement) {
                $cell = [];
                $cellTextArray = [];
                $table = [];

                foreach ($cellElement->getElements() as $element) {
                    if ($this->isTableInstance($element)) {
                        $table = $this->readTable($element);
                    }

                    if ($this->isTextRunInstance($element)) {
                        foreach ($element->getElements() as $element) {
                            if ($this->isTextInstance($element)) {
                                $cellTextArray[] = trim($element->getText());
                            }
                        }
                    }
                }

                if (!empty($table)) {
                    $cell = array_merge($cell, compact('table'));
                }

                if (!empty($cellTextArray)) {
                    $text = implode(' ', $cellTextArray);
                    $cell = array_merge($cell, compact('text'));
                }

                array_push($cells, $cell);
            }
            $row = array_merge($row, compact('cells'));
            array_push($rows, $row);
        }

        return compact('rows');
    }

    private function extractTablesFromElement($element): array
    {
        $tables = [];

        if ($this->isTableInstance($element)) {
            array_push($tables, $element);
        }

        if (!method_exists($element, 'getElements')) {
            return $tables;
        }

        foreach ($element->getElements() as $element) {
            if ($this->isTableInstance($element)) {
                array_push($tables, $element);

                continue;
            }

            $tables = array_merge($tables, $this->extractTablesFromElement($element));
        }

        return $tables;
    }

    private function parseTableElements(array $array): array
    {
        $tables = [];
        foreach ($array as $tableElement) {
            $table = [];
            $rows = [];
            foreach ($tableElement->getRows() as $rowElement) {
                $row = [];
                $cells = [];
                foreach ($rowElement->getCells() as $cellElement) {
                    $cell = $this->fetchCellElements($cellElement);
                    array_push($cells, $cell);
                }
                $row = array_merge($row, compact('cells'));
                array_push($rows, $row);
            }
            $table = array_merge($table, compact('rows'));
            array_push($tables, $table);
        }

        return $tables;
    }

    private function fetchCellElements(Cell $cellElement)
    {
        $cell = [];

        foreach ($cellElement->getElements() as $element) {
            switch (get_class($element)) {
                case Table::class:
                    $table = $this->readTable($element);
                    $cell = array_merge($cell, compact('table'));
                    break;
                case TextRun::class:
                    $text = $this->fetchTextRun($element);
                    $cell = array_merge($cell, compact('text'));
                    break;
                case Text::class:
                    $text = trim($element->getText());
                    $cell = array_merge($cell, compact('text'));
                    break;
            }
        }

        return $cell;
    }

    private function fetchTextRun(TextRun $element)
    {
        $textArray = [];
        foreach ($element->getElements() as $textElement) {
            $textArray[] = trim($textElement->getText());
        }

        return implode(' ', $textArray);
    }

    private function filterRows(array $rows, array $header)
    {
        $columnsCount = count($header) - static::MAX_MISSING_CELLS;

        $keys = collect($header)->keys();

        $rows = collect($rows)
            ->transform(function ($row) {
                $cells = collect($row['cells'] ?? [])
                    ->transform(function ($cell) {
                        $value = $cell['text'] ?? null;

                        if (is_string($value) && !ASCII::is_ascii($value)) {
                            $value = ASCII::to_ascii($value);
                        }

                        if (is_string($value)) {
                            $value = trim($value);
                        }

                        return $value;
                    })
                    ->filter(fn ($value) => filled($value));

                $row['cells'] = $cells;

                return Collection::wrap($row);
            })
            ->reject(fn (SupportCollection $row) => $row->get('cells')->count() < $columnsCount - 1)
            ->transform(function (SupportCollection $row) use ($keys) {
                $cells = $keys->map(fn ($key) => data_get($row, 'cells.'.$key));

                /* One off pay header is last, so we are using last header key. */
                $cells->put($keys->count() - 1, static::seekColumnsForOnePay($cells));

                return $row->put('cells', $cells->toArray())->toArray();
            })
            ->toArray();

        return $rows;
    }

    private static function seekColumnsForOnePay(iterable $cells)
    {
        return SupportCollection::wrap($cells)->contains(fn ($cell) => preg_match(static::REGEXP_ONE_PAY, $cell));
    }

    private function isTableInstance($element)
    {
        return $element instanceof Table;
    }

    private function isTextRunInstance($element)
    {
        return $element instanceof TextRun;
    }

    private function isTextInstance($element)
    {
        return $element instanceof Text;
    }
}
