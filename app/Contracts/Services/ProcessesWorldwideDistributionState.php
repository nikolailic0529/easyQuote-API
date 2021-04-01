<?php

namespace App\Contracts\Services;

use App\DTO\Discounts\DistributionDiscountsCollection;
use App\DTO\DistributionDetailsCollection;
use App\DTO\DistributionExpiryDateCollection;
use App\DTO\DistributionMappingCollection;
use App\DTO\DistributionMarginTaxCollection;
use App\DTO\MappedRow\UpdateMappedRowFieldCollection;
use App\DTO\ProcessableDistributionCollection;
use App\DTO\RowsGroupData;
use App\DTO\SelectedDistributionRowsCollection;
use App\Models\Quote\BaseWorldwideQuote;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;

interface ProcessesWorldwideDistributionState
{
    /**
     * Initialize a new Worldwide Distribution instance.
     *
     * @param WorldwideQuoteVersion $quote
     * @param string|null $opportunitySupplierId
     * @return \App\Models\Quote\WorldwideDistribution
     */
    public function initializeDistribution(WorldwideQuoteVersion $quote, ?string $opportunitySupplierId = null): WorldwideDistribution;

    /**
     * Set expiry date on the Worldwide Distributions.
     *
     * @param DistributionExpiryDateCollection $collection
     * @return void
     */
    public function setDistributionsExpiryDate(DistributionExpiryDateCollection $collection);

    /**
     * Apply Discounts on the Worldwide Distributions.
     *
     * @param WorldwideQuoteVersion $quote
     * @param DistributionDiscountsCollection $collection
     * @return void
     */
    public function applyDistributionsDiscount(WorldwideQuoteVersion $quote, DistributionDiscountsCollection $collection);

    /**
     * Update Details of the Worldwide Distributions.
     *
     * @param WorldwideQuoteVersion $quote
     * @param DistributionDetailsCollection $collection
     * @return void
     */
    public function updateDistributionsDetails(WorldwideQuoteVersion $quote, DistributionDetailsCollection $collection);

    /**
     * Process a single Worldwide Distribution import.
     *
     * @param string $distributionId
     * @return void
     */
    public function processSingleDistributionImport(string $distributionId);

    /**
     * Process Worldwide Distributions import.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\ProcessableDistributionCollection $collection
     * @return mixed
     */
    public function processDistributionsImport(WorldwideQuoteVersion $quote, ProcessableDistributionCollection $collection);

    /**
     * @param ProcessableDistributionCollection $collection
     * @return MessageBag
     */
    public function validateDistributionsAfterImport(ProcessableDistributionCollection $collection): MessageBag;

    /**
     * Process Worldwide Distributions mapping.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\DistributionMappingCollection $collection
     * @return mixed
     */
    public function processDistributionsMapping(WorldwideQuoteVersion $quote, DistributionMappingCollection $collection);

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\SelectedDistributionRowsCollection $collection
     * @return mixed
     */
    public function updateRowsSelection(WorldwideQuoteVersion $quote, SelectedDistributionRowsCollection $collection);

    /**
     * Set margin of Worldwide Distributions.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\DTO\DistributionMarginTaxCollection $collection
     * @return void
     */
    public function setDistributionsMargin(WorldwideQuoteVersion $quote, DistributionMarginTaxCollection $collection);

    /**
     * Create a new rows group of worldwide distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\DTO\RowsGroupData $data
     * @return \App\Models\QuoteFile\DistributionRowsGroup
     */
    public function createRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Update the rows group of worldwide distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $rowsGroup
     * @param \App\DTO\RowsGroupData $data
     * @return \App\Models\QuoteFile\DistributionRowsGroup
     */
    public function updateRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Delete the rows group of worldwide distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $rowsGroup
     * @return void
     */
    public function deleteRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup): void;

    /**
     * Move rows between the groups of worldwide distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $worldwideWorldwideDistribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $outputRowsGroup
     * @param \App\Models\QuoteFile\DistributionRowsGroup $inputRowsGroup
     * @param array $rows
     * @return void
     */
    public function moveRowsBetweenGroups(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideWorldwideDistribution, DistributionRowsGroup $outputRowsGroup, DistributionRowsGroup $inputRowsGroup, array $rows): void;

    /**
     * Store a new Distributor File to the Worldwide Distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @param \Illuminate\Http\UploadedFile $file
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function storeDistributorFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile;

    /**
     * Store a new Payment Schedule File to the Worldwide Distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @param \Illuminate\Http\UploadedFile $file
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function storeScheduleFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile;

    /**
     * Delete the specified Distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @return boolean
     */
    public function deleteDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution): bool;

    /**
     * Update the specified row of the Worldwide Distribution.
     *
     * @param WorldwideQuoteVersion $quote
     * @param WorldwideDistribution $worldwideDistribution
     * @param MappedRow $mappedRow
     * @param UpdateMappedRowFieldCollection $rowData
     * @return MappedRow
     */
    public function updateMappedRowOfDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, MappedRow $mappedRow, UpdateMappedRowFieldCollection $rowData): MappedRow;
}
