<?php

namespace App\Domain\DocumentProcessing\Readers\Contracts;

interface CsvParserInterface
{
    /**
     * Guess possible delimiter for CSV file.
     */
    public function guessDelimiter(string $path): string;
}
