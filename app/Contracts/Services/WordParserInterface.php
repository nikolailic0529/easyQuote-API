<?php namespace App\Contracts\Services;

use Devengine\PhpWord\PhpWord;

interface WordParserInterface
{
    /**
     * Load file and return PhpWord instance reader
     *
     * @param string $path
     * @return App\Services\WordParser\WordParser
     */
    public function load(string $path);

    /**
     * Extract and return raw text from Word file
     *
     * @param string $path
     * @return array
     */
    public function getText(string $path);

    /**
     * Extract all first level tables from DOCX format file
     *
     * @param PhpWord $phpWord
     * @return Array
     */
    public function getTables(PhpWord $phpWord);

    /**
     * Recursive search header by $needle and rows on the same nesting level
     * 
     * @param string $needle
     * @param Array $tables
     * @return Array [[header][rows]]
     */
    public function findRows(string $needle, array $tables);
}
