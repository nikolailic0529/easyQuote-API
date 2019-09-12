<?php namespace App\Services;

use App\Contracts\Services\WordParserInterface;
use Devengine\PhpWord \ {
    IOFactory,
    PhpWord,
    Element\Table,
    Element\TextRun,
    Element\Text,
    Element\Cell
};
use Illuminate\Database\Eloquent\Collection;
use Storage, Log;

class WordParser implements WordParserInterface
{
    protected $phpWord;

    protected $tables;

    protected $minHeadersCount;

    public function __construct()
    {
        $this->tables = [];
        $this->minHeadersCount = 3;
    }

    public function load(string $path)
    {
        $path = Storage::path($path);

        $this->phpWord = IOFactory::load($path);

        return $this;
    }

    public function getTables(PhpWord $phpWord = null)
    {
        if(is_null($phpWord)) {
            $phpWord = $this->phpWord;
        }

        foreach ($phpWord->getSections() as $section) {
            $this->extractTables($section);
        }

        $this->tables = $this->fetchTables($this->tables);

        return $this;
    }

    public function getRows(Collection $columns, WordParser $wordParser = null)
    {
        if(is_null($wordParser)) {
            $wordParser = $this;
        }

        $rows = [];

        foreach ($columns as $column) {
            $aliases = $column->aliases;
            
            $foundRowsByColumn = [];

            foreach ($aliases as $alias) {
                $foundRowsByAlias = $wordParser->findRows($alias->alias);

                if(!empty($foundRowsByAlias)) {
                    $foundRowsByColumn = $foundRowsByAlias;
                    break;
                }
            }

            if(!empty($foundRowsByColumn)) {
                $rows = $foundRowsByColumn;
                break;
            }
        }

        return $rows;
    }

    public function findRows(string $needle, array $tables = [])
    {
        if(empty($tables)) {
            $tables = $this->tables;
        }

        $cellPath = $this->findCell($tables, $needle);

        if(!$cellPath) {
            return [];
        }

        $header = $this->normalizeHeader($tables, $cellPath);

        if(count($header) < $this->minHeadersCount) {
            return [];
        }

        $rowsPath = array_splice($cellPath, 0, -3);

        $rows = $tables;

        foreach ($rowsPath as $key) {
            $rows = $rows[$key];
        }

        /**
         * Remove header row
         */
        $rows = array_splice($rows, 1);

        $rows = $this->filterRows($rows, $header);

        return compact('header', 'rows');
    }

    private function normalizeHeader(array $tables, array $path) {
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
        $coverageSearch = preg_grep('/^Coverage period/i', $row);

        if(empty($coverageSearch)) {
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

    private function simplifyRow(array $row) {
        $simpleRow = [];

        foreach ($row as $cell) {
            array_push($simpleRow, $this->extractTextValue($cell));
        }

        return $simpleRow;
    }

    private function extractTextValue(array $array) {
        if(isset($array['text'])) {
            return $array['text'];
        }

        foreach ($array as $key => $value) {
            if ($key === 'text') {
                return $value['text'];
            }

            if(is_array($value) && $result = $this->extractTextValue($value)) {
                return $result;
            }
        }
        
        return false;
    }

    private function findCell(array $tables, string $needle, array $path = [])
    {
        $needle = mb_strtolower($needle);

        foreach ($tables as $key => $value) {
            $currentPath = array_merge($path, [$key]);

            if (is_array($value) && $result = $this->findCell($value, $needle, $currentPath)) {
                return $result;
            }

            if (
                isset($value['text']) &&
                mb_strtolower($value['text']) === $needle
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
            
            foreach($rowElement->getCells() as $cellElement) {
                $cell = [];
                $cellTextArray = [];
                $table = [];
                
                foreach($cellElement->getElements() as $element) {

                    if($this->isTableInstance($element)) {
                        $table = $this->readTable($element);
                    }

                    if($this->isTextRunInstance($element)) {
                        foreach ($element->getElements() as $element) {
                            if($this->isTextInstance($element)) {
                                $cellTextArray[] = trim($element->getText());
                            }
                        }
                    }
                }
                
                if(!empty($table)) {
                    $cell = array_merge($cell, compact('table'));
                }

                if(!empty($cellTextArray)) {
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

    private function extractTables($element)
    {
        if($this->isTableInstance($element)) {
            array_push($this->tables, $element);
        }

        if(!method_exists($element, 'getElements')) {
            return false;
        }

        foreach ($element->getElements() as $element) {
            if($this->isTableInstance($element)) {
                return array_push($this->tables, $element);
            }

            $this->extractTables($element);
        }
    }

    private function fetchTables(array $array)
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
        $columnsCount = count($header);

        $mappedRows = array_map(function ($row) {
           $cells = array_filter($row['cells'], function ($cell) {
               return isset($cell['text']);
           });

           $cells = array_map(function ($cell) {
                return $cell['text'];
           }, $cells);

           return compact('cells');
        }, $rows);

        $filteredRows = array_filter($mappedRows, function ($row) use ($columnsCount) {
            return isset($row['cells']) && count($row['cells']) >= $columnsCount;
        });

        return array_values($filteredRows);
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
