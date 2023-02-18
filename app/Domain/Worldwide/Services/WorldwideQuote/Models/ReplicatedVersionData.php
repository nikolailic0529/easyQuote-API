<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Models;

use App\Domain\Note\Models\Note;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;

final class ReplicatedVersionData
{
    /**
     * ReplicatedVersionData constructor.
     *
     * @param \App\Domain\Worldwide\Models\WorldwideQuoteAsset[] $replicatedPackAssets
     * @param ReplicatedDistributorQuoteData[]                   $replicatedDistributorQuotes
     */
    public function __construct(protected WorldwideQuoteVersion $replicatedVersion,
                                protected array $addressPivots,
                                protected array $contactPivots,
                                protected array $replicatedPackAssets,
                                protected array $replicatedAssetsGroups,
                                protected array $replicatedAssetsOfGroups,
                                protected array $replicatedDistributorQuotes,
                                protected ?Note $replicatedQuoteNote)
    {
    }

    public function getReplicatedVersion(): WorldwideQuoteVersion
    {
        return $this->replicatedVersion;
    }

    /**
     * @return \App\Domain\Worldwide\Models\WorldwideQuoteAsset[]
     */
    public function getReplicatedPackAssets(): array
    {
        return $this->replicatedPackAssets;
    }

    /**
     * @return ReplicatedDistributorQuoteData[]
     */
    public function getReplicatedDistributorQuotes(): array
    {
        return $this->replicatedDistributorQuotes;
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

    public function getReplicatedQuoteNote(): ?Note
    {
        return $this->replicatedQuoteNote;
    }

    /**
     * @return \App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup[]
     */
    public function getReplicatedAssetsGroups(): array
    {
        return $this->replicatedAssetsGroups;
    }

    /**
     * @return array[]
     */
    public function getReplicatedAssetsOfGroups(): array
    {
        return $this->replicatedAssetsOfGroups;
    }
}
