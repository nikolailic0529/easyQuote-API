<?php namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;

interface ParserServiceInterface
{
    /**
     * Get raw text from all pages of Quote File
     *
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Support\Collection
     */
    public function getPdfText(QuoteFile $quoteFile);

    /**
     * Parse raw text by columns
     *
     * @param String $text
     * @return void
     */
    public function parsePdfText(String $text);

    /**
     * Determine Quote File format and return the related parser
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function routeParser(QuoteFile $quoteFile);

    /**
     * Handle PDF file
     *
     * @param QuoteFile $quoteFile
     * @return \App\Models\QuoteFile\ImportedColumnData
     */
    public function handlePdf(QuoteFile $quoteFile);
}
