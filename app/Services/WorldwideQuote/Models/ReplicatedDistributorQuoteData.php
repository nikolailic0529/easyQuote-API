<?php

namespace App\Services\WorldwideQuote\Models;

use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;

final class ReplicatedDistributorQuoteData
{
    protected WorldwideDistribution $distributorQuote;

    protected ReplicatedAddressesData $replicatedAddressesData;

    protected ReplicatedContactsData $replicatedContactsData;

    protected array $mapping;

    protected array $rowsGroups;

    protected array $groupRows;

    protected array $mappedRows;

    protected ?QuoteFile $distributorFile;

    protected array $importedRows;

    protected ?QuoteFile $scheduleFile;

    protected ?ScheduleData $scheduleData;

    /**
     * ReplicatedDistributorQuoteData constructor.
     * @param WorldwideDistribution $distributorQuote
     * @param array $mapping
     * @param array $rowsGroups
     * @param array $groupRows
     * @param array $mappedRows
     * @param QuoteFile|null $distributorFile
     * @param array $importedRows
     * @param QuoteFile|null $scheduleFile
     * @param ScheduleData|null $scheduleData
     */
    public function __construct(WorldwideDistribution $distributorQuote,
                                ReplicatedAddressesData $replicatedAddressesData,
                                ReplicatedContactsData $replicatedContactsData,
                                array $mapping,
                                array $rowsGroups,
                                array $groupRows,
                                array $mappedRows,
                                ?QuoteFile $distributorFile,
                                array $importedRows,
                                ?QuoteFile $scheduleFile,
                                ?ScheduleData $scheduleData)
    {
        $this->distributorQuote = $distributorQuote;

        $this->replicatedAddressesData = $replicatedAddressesData;
        $this->replicatedContactsData = $replicatedContactsData;
        $this->mapping = $mapping;
        $this->rowsGroups = $rowsGroups;
        $this->groupRows = $groupRows;
        $this->mappedRows = $mappedRows;
        $this->distributorFile = $distributorFile;
        $this->importedRows = $importedRows;
        $this->scheduleFile = $scheduleFile;
        $this->scheduleData = $scheduleData;
    }

    /**
     * @return WorldwideDistribution
     */
    public function getDistributorQuote(): WorldwideDistribution
    {
        return $this->distributorQuote;
    }

    /**
     * @return \App\Services\WorldwideQuote\Models\ReplicatedAddressesData
     */
    public function getReplicatedAddressesData(): ReplicatedAddressesData
    {
        return $this->replicatedAddressesData;
    }

    /**
     * @return \App\Services\WorldwideQuote\Models\ReplicatedContactsData
     */
    public function getReplicatedContactsData(): ReplicatedContactsData
    {
        return $this->replicatedContactsData;
    }

    /**
     * @return DistributionFieldColumn[]
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @return DistributionRowsGroup[]
     */
    public function getRowsGroups(): array
    {
        return $this->rowsGroups;
    }

    /**
     * @return MappedRow[]
     */
    public function getMappedRows(): array
    {
        return $this->mappedRows;
    }

    /**
     * @return QuoteFile|null
     */
    public function getDistributorFile(): ?QuoteFile
    {
        return $this->distributorFile;
    }

    /**
     * @return QuoteFile|null
     */
    public function getScheduleFile(): ?QuoteFile
    {
        return $this->scheduleFile;
    }

    /**
     * @return ScheduleData|null
     */
    public function getScheduleData(): ?ScheduleData
    {
        return $this->scheduleData;
    }

    /**
     * @return ImportedRow[]
     */
    public function getImportedRows(): array
    {
        return $this->importedRows;
    }

    /**
     * @return array[]
     */
    public function getGroupRows(): array
    {
        return $this->groupRows;
    }
}
