<?php namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};

interface ParserServiceInterface
{
    /**
     * Parse raw text by columns from XLSX format Quote File
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function importExcel(QuoteFile $quoteFile);

    /**
     * Determine Quote File format and return the related parser
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    public function routeParser(QuoteFile $quoteFile);

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
     * @return \Illuminate\Support\Collection
     */
    public function handlePdf(QuoteFile $quoteFile);

    /**
     * Handle Excel format Quote File and store parsed data
     * Return collection as parsed rows
     * @param QuoteFile $quoteFile
     * @return \Illuminate\Support\Collection
     */
    public function handleExcel(QuoteFile $quoteFile);

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
}
