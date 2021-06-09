<?php

namespace App\Contracts\Services;

use App\DTO\MappedRowSettings;
use App\DTO\RowMapping;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Quote\Quote;
use Closure;
use Illuminate\Support\Manager;
use Psr\Log\LoggerInterface;

interface ManagesDocumentProcessors
{
    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback);

    /**
     * Get the container instance used by the manager.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer();

    /**
     * Get logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface;

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

    /**
     * Transit imported rows to mapped rows.
     *
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @param \App\DTO\RowMapping $rowMapping
     * @param MappedRowSettings|null $rowSettings
     * @return void
     * @poaram \App\DTO\MappedRowDefaults $defaults
     */
    public function transitImportedRowsToMappedRows(QuoteFile $quoteFile, RowMapping $rowMapping, ?MappedRowSettings $rowSettings = null);
}
