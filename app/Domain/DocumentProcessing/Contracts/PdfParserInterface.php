<?php

namespace App\Domain\DocumentProcessing\Contracts;

interface PdfParserInterface
{
    /**
     * Extract and return raw text from PDF file collected by pages.
     *
     * @return array
     */
    public function getText(string $path);

    /**
     * Parse PDF text by columns.
     *
     * @return array
     */
    public function parse(array $array);

    /**
     * Parse PDF schedule raw text.
     *
     * @return array
     */
    public function parseSchedule(array $array);

    /**
     * Count pages in PDF file.
     *
     * @return int
     */
    public function countPages(string $path);
}
