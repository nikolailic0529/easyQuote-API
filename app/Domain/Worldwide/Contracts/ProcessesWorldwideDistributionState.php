<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Discount\DataTransferObjects\DistributionDiscountsCollection;
use App\Domain\DocumentMapping\DataTransferObjects\UpdateMappedRowFieldCollection;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionDetailsCollection;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionExpiryDateCollection;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMappingCollection;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMarginTaxCollection;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\ProcessableDistributionCollection;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\SelectedDistributionRowsCollection;
use App\Domain\Worldwide\DataTransferObjects\RowsGroupData;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;

interface ProcessesWorldwideDistributionState
{
    /**
     * Initialize a new Worldwide Distribution instance.
     */
    public function initializeDistribution(WorldwideQuoteVersion $quote, ?string $opportunitySupplierId = null): WorldwideDistribution;

    /**
     * Sync data of the Distributor Quote with own Opportunity Supplier.
     *
     * @return WorldwideDistribution
     */
    public function syncDistributionWithOwnOpportunitySupplier(WorldwideDistribution $distributorQuote): void;

    /**
     * Set expiry date on the Worldwide Distributions.
     *
     * @return void
     */
    public function setDistributionsExpiryDate(DistributionExpiryDateCollection $collection);

    /**
     * Apply Discounts on the Worldwide Distributions.
     *
     * @return void
     */
    public function applyDistributionsDiscount(WorldwideQuoteVersion $quote, DistributionDiscountsCollection $collection);

    /**
     * Update Details of the Worldwide Distributions.
     *
     * @return void
     */
    public function updateDistributionsDetails(WorldwideQuoteVersion $quote, DistributionDetailsCollection $collection);

    /**
     * Process a single Worldwide Distribution import.
     *
     * @return void
     */
    public function processSingleDistributionImport(string $distributionId);

    /**
     * Process Worldwide Distributions import.
     *
     * @return mixed
     */
    public function processDistributionsImport(WorldwideQuoteVersion $quote, ProcessableDistributionCollection $collection);

    public function validateDistributionsAfterImport(ProcessableDistributionCollection $collection): MessageBag;

    /**
     * Process Worldwide Distributions mapping.
     *
     * @return mixed
     */
    public function processDistributionsMapping(WorldwideQuoteVersion $quote, DistributionMappingCollection $collection);

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @return mixed
     */
    public function updateRowsSelection(WorldwideQuoteVersion $quote, SelectedDistributionRowsCollection $collection);

    /**
     * Set margin of Worldwide Distributions.
     *
     * @return void
     */
    public function setDistributionsMargin(WorldwideQuoteVersion $quote, DistributionMarginTaxCollection $collection);

    /**
     * Create a new rows group of worldwide distribution.
     */
    public function createRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Update the rows group of worldwide distribution.
     */
    public function updateRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Delete the rows group of worldwide distribution.
     */
    public function deleteRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup): void;

    /**
     * Move rows between the groups of worldwide distribution.
     */
    public function moveRowsBetweenGroups(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideWorldwideDistribution, DistributionRowsGroup $outputRowsGroup, DistributionRowsGroup $inputRowsGroup, array $rows): void;

    /**
     * Store a new Distributor File to the Worldwide Distribution.
     */
    public function storeDistributorFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile;

    /**
     * Store a new Payment Schedule File to the Worldwide Distribution.
     */
    public function storeScheduleFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile;

    /**
     * Delete the specified Distribution.
     */
    public function deleteDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution): bool;

    /**
     * Update the specified row of the Worldwide Distribution.
     */
    public function updateMappedRowOfDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, MappedRow $mappedRow, UpdateMappedRowFieldCollection $rowData): MappedRow;
}
