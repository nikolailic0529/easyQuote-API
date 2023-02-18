<?php

namespace App\Domain\DocumentProcessing\Contracts;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\QuoteFileFormat;
use App\Domain\QuoteFile\Requests\HandleQuoteFileRequest;
use App\Domain\QuoteFile\Requests\{StoreQuoteFileRequest};
use App\Domain\Rescue\Models\Quote;

interface ParserServiceInterface
{
    /**
     * Determine Quote File format and return the related parser.
     */
    public function routeParser(QuoteFile $quoteFile): void;

    /**
     * Check for existing handling.
     *
     * @return mixed
     */
    public function handle(HandleQuoteFileRequest $request);

    /**
     * Extract number of sheets/pages and check for errors in Quote File.
     *
     * @param \App\Domain\QuoteFile\Requests\StoreQuoteFileRequest $request
     */
    public function preHandle(StoreQuoteFileRequest $request): array;

    /**
     * Determine File format before storing.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function determineFileFormat(string $path): QuoteFileFormat;

    /**
     * Count pages/sheets in the uploaded Quote File depends on file format.
     */
    public function countPages(string $path, bool $storage = true): int;

    /**
     * Map known columns to fields in the Quote.
     *
     * @return void
     */
    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile);
}
