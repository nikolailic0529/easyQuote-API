<?php

namespace App\Domain\DocumentProcessing\Contracts;

use App\Domain\DocumentMapping\DataTransferObjects\MappingConfig;
use App\Domain\DocumentMapping\DataTransferObjects\RowMapping;
use App\Domain\QuoteFile\Models\QuoteFile;
use Closure;
use Illuminate\Support\Manager;
use Psr\Log\LoggerInterface;

interface ManagesDocumentProcessors
{
    public function driver($driver = null): ProcessesQuoteFile;

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     *
     * @return static
     */
    public function extend($driver, \Closure $callback);

    /**
     * Get the container instance used by the manager.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer();

    /**
     * Get logger.
     */
    public function getLogger(): LoggerInterface;

    /**
     * Resolve the appropriate document processor and perform processing.
     */
    public function forwardProcessor(QuoteFile $quoteFile): void;

    /**
     * Transit imported rows to mapped rows.
     *
     * @return void
     *
     * @poaram \App\DTO\MappedRowDefaults $defaults
     */
    public function transitImportedRowsToMappedRows(QuoteFile $quoteFile, RowMapping $rowMapping, ?MappingConfig $mappingConfig = null);
}
