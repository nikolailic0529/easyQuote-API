<?php namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};

interface ParserServiceInterface
{
    /**
     * Get raw text from all pages of PDF format Quote File
     *
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Support\Collection
     */
    public function getPdfText(QuoteFile $quoteFile);

    /**
     * Parse raw text by columns from PDF format Quote File
     *
     * @param Array $pages
     * @return array
     */
    public function parsePdfText(Array $pages);

    /**
     * Get raw text from all pages of CSV format Quote File
     *
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Support\Collection
     */
    public function getCsvText(QuoteFile $quoteFile);

    /**
     * Parse raw text by columns from CSV format Quote File
     *
     * @param String $text
     * @return array
     */
    public function parseCsvText(String $text, QuoteFile $quoteFile);

    /**
     * Determine Quote File format and return the related parser
     *
     * @param QuoteFile $quoteFile
     * @param Int $page
     * @return void
     */
    public function routeParser(QuoteFile $quoteFile, Int $page);

    /**
     * Check for existing handling
     *
     * @param HandleQuoteFileRequest $request
     * @param Int $page
     * @return void
     */
    public function handle(HandleQuoteFileRequest $request);

    /**
     * Extract number of sheets/pages and check for errors in Quote File
     *  
     * @param QuoteFile $quoteFile
     * @return QuoteFile
     */
    public function preHandle(StoreQuoteFileRequest $request);

    /**
     * Handle PDF format Quote File and store parsed data
     * Return collection as parsed rows
     * @param QuoteFile $quoteFile
     * @param Int $page
     * @return \Illuminate\Support\Collection
     */
    public function handlePdf(QuoteFile $quoteFile, Int $page);

    /**
     * Handle CSV format Quote File and store parsed data
     * Return collection as parsed rows
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Support\Collection
     */
    public function handleCsv(QuoteFile $quoteFile);

    /**
     * Determine File format before storing
     * Will throw exception, if File Format doesn't exist
     *
     * @param String $path
     * @return \App\Models\QuoteFile\QuoteFileFormat
     */
    public function determineFileFormat(String $path);

    /**
     * Count pages/sheets in the uploaded Quote File depends on file format
     *
     * @param String $path
     * @return int
     */
    public function countPages(String $path);

    /**
     * Count pages in PDF format file
     *
     * @param String $path
     * @return int
     */
    public function countPdfPages(String $path);
}
