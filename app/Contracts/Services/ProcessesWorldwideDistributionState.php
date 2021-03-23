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
     * @param BaseWorldwideQuote $quote
     * @param string|null $opportunitySupplierId
     * @return \App\Models\Quote\WorldwideDistribution
     */
    public function initializeDistribution(BaseWorldwideQuote $quote, ?string $opportunitySupplierId = null): WorldwideDistribution;

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
     * @param DistributionDiscountsCollection $collection
     * @return void
     */
    public function applyDistributionsDiscount(DistributionDiscountsCollection $collection);

    /**
     * Update Details of the Worldwide Distributions.
     *
     * @param DistributionDetailsCollection $collection
     * @return void
     */
    public function updateDistributionsDetails(DistributionDetailsCollection $collection);

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
     * @param \App\DTO\ProcessableDistributionCollection $collection
     * @return mixed
     */
    public function processDistributionsImport(ProcessableDistributionCollection $collection);

    /**
     * @param ProcessableDistributionCollection $collection
     * @return MessageBag
     */
    public function validateDistributionsAfterImport(ProcessableDistributionCollection $collection): MessageBag;

    /**
     * Process Worldwide Distributions mapping.
     *
     * @param \App\DTO\DistributionMappingCollection $collection
     * @return mixed
     */
    public function processDistributionsMapping(DistributionMappingCollection $collection);

    /**
     * Update rows selection of Worldwide Distributions.
     *
     * @param \App\DTO\SelectedDistributionRowsCollection $collection
     * @return mixed
     */
    public function updateRowsSelection(SelectedDistributionRowsCollection $collection);

    /**
     * Set margin of Worldwide Distributions.
     *
     * @param \App\DTO\DistributionMarginTaxCollection $collection
     * @return void
     */
    public function setDistributionsMargin(DistributionMarginTaxCollection $collection);

    /**
     * Create a new rows group of worldwide distribution.
     *
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\DTO\RowsGroupData $data
     * @return \App\Models\QuoteFile\DistributionRowsGroup
     */
    public function createRowsGroup(WorldwideDistribution $distribution, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Update the rows group of worldwide distribution.
     *
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $rowsGroup
     * @param \App\DTO\RowsGroupData $data
     * @return \App\Models\QuoteFile\DistributionRowsGroup
     */
    public function updateRowsGroup(WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup, RowsGroupData $data): DistributionRowsGroup;

    /**
     * Delete the rows group of worldwide distribution.
     *
     * @param \App\Models\Quote\WorldwideDistribution $distribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $rowsGroup
     * @return void
     */
    public function deleteRowsGroup(WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup): void;

    /**
     * Move rows between the groups of worldwide distribution.
     *
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @param \App\Models\QuoteFile\DistributionRowsGroup $outputRowsGroup
     * @param \App\Models\QuoteFile\DistributionRowsGroup $inputRowsGroup
     * @param array $rows
     * @return void
     */
    public function moveRowsBetweenGroups(WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $outputRowsGroup, DistributionRowsGroup $inputRowsGroup, array $rows): void;

    /**
     * Store a new Distributor File to the Worldwide Distribution.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function storeDistributorFile(UploadedFile $file, WorldwideDistribution $worldwideDistribution): QuoteFile;

    /**
     * Store a new Payment Schedule File to the Worldwide Distribution.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @return \App\Models\QuoteFile\QuoteFile
     */
    public function storeScheduleFile(UploadedFile $file, WorldwideDistribution $worldwideDistribution): QuoteFile;

    /**
     * Delete the specified Distribution.
     *
     * @param \App\Models\Quote\WorldwideDistribution $worldwideDistribution
     * @return boolean
     */
    public function deleteDistribution(WorldwideDistribution $worldwideDistribution): bool;

    /**
     * Update the specified row of the Worldwide Distribution.
     *
     * @param UpdateMappedRowFieldCollection $rowData
     * @param MappedRow $mappedRow
     * @param WorldwideDistribution $worldwideDistribution
     * @return MappedRow
     */
    public function updateMappedRowOfDistribution(UpdateMappedRowFieldCollection $rowData, MappedRow $mappedRow, WorldwideDistribution $worldwideDistribution): MappedRow;
}
