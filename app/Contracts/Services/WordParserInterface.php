<?php

namespace App\Contracts\Services;

use PhpOffice\PhpWord\PhpWord;

interface WordParserInterface
{
    /**
     * Load file and return PhpWord instance reader
     *
     * @param string $path
     * @return $this
     */
    public function load(string $path): static;

    /**
     * Parse Docx Distributor File.
     *
     * @param  string $filePath
     * @return array
     */
    public function parseAsDistributorFile(string $filePath): array;

    /**
     * Extract all first level tables from DOCX format file
     *
     * @param PhpWord|null $phpWord
     * @return array
     */
    public function getTables(?PhpWord $phpWord = null): array;

    /**
     * Recursive search header by $needle and rows on the same nesting level
     *
     * @param string $needle
     * @param array $tables
     * @return array [[header][rows]]
     */
    public function findRows(string $needle, array $tables = []): array;
}
