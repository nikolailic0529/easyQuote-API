<?php

namespace App\Services\WorldwideQuote\Models;

use App\Models\Note\Note;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\WorldwideQuoteAsset;

final class ReplicatedVersionData
{

    /**
     * ReplicatedVersionData constructor.
     * @param WorldwideQuoteVersion $replicatedVersion
     * @param array $addressPivots
     * @param array $contactPivots
     * @param WorldwideQuoteAsset[] $replicatedPackAssets
     * @param array $replicatedAssetsGroups
     * @param array $replicatedAssetsOfGroups
     * @param ReplicatedDistributorQuoteData[] $replicatedDistributorQuotes
     * @param Note|null $replicatedQuoteNote
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

    /**
     * @return WorldwideQuoteVersion
     */
    public function getReplicatedVersion(): WorldwideQuoteVersion
    {
        return $this->replicatedVersion;
    }

    /**
     * @return WorldwideQuoteAsset[]
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

    /**
     * @return Note|null
     */
    public function getReplicatedQuoteNote(): ?Note
    {
        return $this->replicatedQuoteNote;
    }

    /**
     * @return \App\Models\WorldwideQuoteAssetsGroup[]
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
