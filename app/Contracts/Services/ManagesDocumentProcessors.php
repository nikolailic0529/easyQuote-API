<?php

namespace App\Contracts\Services;

use App\DTO\MappingConfig;
use App\DTO\RowMapping;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Quote\Quote;
use Closure;
use Illuminate\Support\Manager;
use Psr\Log\LoggerInterface;

interface ManagesDocumentProcessors
{
    public function driver($driver = null): ProcessesQuoteFile;

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return static
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
     * Transit imported rows to mapped rows.
     *
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @param \App\DTO\RowMapping $rowMapping
     * @param MappingConfig|null $mappingConfig
     * @return void
     * @poaram \App\DTO\MappedRowDefaults $defaults
     */
    public function transitImportedRowsToMappedRows(QuoteFile $quoteFile, RowMapping $rowMapping, ?MappingConfig $mappingConfig = null);
}
