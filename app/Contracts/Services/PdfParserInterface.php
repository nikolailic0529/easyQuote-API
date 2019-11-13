<?php

namespace App\Contracts\Services;

interface PdfParserInterface
{
    /**
     * Extract and return raw text from PDF file collected by pages
     *
     * @param string $path
     * @param bool $storage
     * @return array
     */
    public function getText(string $path, bool $storage = true);

    /**
     * Parse PDF text by columns
     *
     * @param array $array
     * @return array
     */
    public function parse(array $array);

    /**
     * Count pages in PDF file
     *
     * @param string $path
     * @param bool $storage
     * @return int
     */
    public function countPages(string $path, bool $storage = true);
}
