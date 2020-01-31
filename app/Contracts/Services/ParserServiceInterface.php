<?php

namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use App\Http\Requests\{
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use App\Models\Quote\Quote;
use App\Models\QuoteFile\QuoteFileFormat;

interface ParserServiceInterface
{
    /**
     * Determine Quote File format and return the related parser
     *
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return void
     */
    public function routeParser(QuoteFile $quoteFile): void;

    /**
     * Check for existing handling
     *
     * @param \App\Http\Requests\HandleQuoteFileRequest $request
     * @return mixed
     */
    public function handle(HandleQuoteFileRequest $request);

    /**
     * Extract number of sheets/pages and check for errors in Quote File
     *
     * @param \App\Http\Requests\StoreQuoteFileRequest $request
     * @return array
     */
    public function preHandle(StoreQuoteFileRequest $request): array;

    /**
     * Determine File format before storing
     *
     * @param string $path
     * @return \App\Models\QuoteFile\QuoteFileFormat
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function determineFileFormat(string $path): QuoteFileFormat;

    /**
     * Count pages/sheets in the uploaded Quote File depends on file format
     *
     * @param string $path
     * @return int
     */
    public function countPages(string $path, bool $storage = true): int;

    /**
     * Map known columns to fields in the Quote.
     *
     * @param \App\Models\Quote\Quote $quote
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return void
     */
    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile);
}
