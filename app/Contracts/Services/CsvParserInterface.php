<?php

namespace App\Contracts\Services;

interface CsvParserInterface
{
    /**
     * Guess possible delimiter for CSV file.
     *
     * @param string $path
     * @return string
     */
    public function guessDelimiter(string $path): string;
}
