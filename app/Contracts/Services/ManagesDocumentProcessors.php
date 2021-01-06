<?php

namespace App\Contracts\Services;

use App\Models\QuoteFile\QuoteFile;
use App\Http\Requests\{
    HandleQuoteFileRequest
};
use App\Models\Quote\Quote;

interface ManagesDocumentProcessors
{
    /**
     * Resolve the appropriate document processor and perform processing.
     *
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return void
     */
    public function forwardProcessor(QuoteFile $quoteFile): void;

    /**
     * Perform the Quote File processing.
     *
     * @param \App\Models\Quote\Quote $quote
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @param integer|null $importablePageNumber
     * @return mixed
     */
    public function performProcess(Quote $quote, QuoteFile $quoteFile, ?int $importablePageNumber = null);

    /**
     * Map known columns to fields in the Quote.
     *
     * @param \App\Models\Quote\Quote $quote
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return void
     */
    public function mapColumnsToFields(Quote $quote, QuoteFile $quoteFile);
}
