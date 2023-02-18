<?php

namespace App\Domain\DocumentProcessing\Contracts;

use PhpOffice\PhpWord\PhpWord;

interface WordParserInterface
{
    /**
     * Load file and return PhpWord instance reader.
     *
     * @return $this
     */
    public function load(string $path): static;

    /**
     * Parse Docx Distributor File.
     */
    public function parseAsDistributorFile(string $filePath): array;

    /**
     * Extract all first level tables from DOCX format file.
     */
    public function getTables(?PhpWord $phpWord = null): array;

    /**
     * Recursive search header by $needle and rows on the same nesting level.
     *
     * @return array [[header][rows]]
     */
    public function findRows(string $needle, array $tables = []): array;
}
