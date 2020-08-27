<?php

namespace App\Contracts\Services;

use Devengine\PhpWord\PhpWord;

interface WordParserInterface
{
    /**
     * Load file and return PhpWord instance reader
     *
     * @param string $path
     * @param bool $storage
     * @return $this
     */
    public function load(string $path, bool $storage = true);

    /**
     * Extract and return raw text from Word file
     *
     * @param string $path
     * @param bool $storage
     * @return array
     */
    public function getText(string $path, bool $storage = true);

    /**
     * Extract all first level tables from DOCX format file
     *
     * @param PhpWord|null $phpWord
     * @return $this
     */
    public function getTables(?PhpWord $phpWord = null);

    /**
     * Recursive search header by $needle and rows on the same nesting level
     *
     * @param string $needle
     * @param array $tables
     * @return array [[header][rows]]
     */
    public function findRows(string $needle, array $tables = []);
}
