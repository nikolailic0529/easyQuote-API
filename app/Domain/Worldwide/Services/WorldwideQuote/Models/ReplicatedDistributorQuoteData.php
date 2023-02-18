<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Models;

use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\WorldwideDistribution;

final class ReplicatedDistributorQuoteData
{
    /**
     * ReplicatedDistributorQuoteData constructor.
     */
    public function __construct(protected WorldwideDistribution $distributorQuote,
                                protected array $vendorPivots,
                                protected array $addressPivots,
                                protected array $contactPivots,
                                protected array $mapping,
                                protected array $rowsGroups,
                                protected array $groupRows,
                                protected array $mappedRows,
                                protected ?QuoteFile $distributorFile,
                                protected array $importedRows,
                                protected ?QuoteFile $scheduleFile,
                                protected ?ScheduleData $scheduleData)
    {
    }

    public function getDistributorQuote(): WorldwideDistribution
    {
        return $this->distributorQuote;
    }

    /**
     * @return \App\Domain\Worldwide\Models\DistributionFieldColumn[]
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

    public function getDistributorFile(): ?QuoteFile
    {
        return $this->distributorFile;
    }

    public function getScheduleFile(): ?QuoteFile
    {
        return $this->scheduleFile;
    }

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

    /**
     * @return array[]
     */
    public function getAddressPivots(): array
    {
        return $this->addressPivots;
    }

    /**
     * @return array[]
     */
    public function getContactPivots(): array
    {
        return $this->contactPivots;
    }

    /**
     * @return array[]
     */
    public function getVendorPivots(): array
    {
        return $this->vendorPivots;
    }
}
